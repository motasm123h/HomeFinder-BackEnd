<?php

namespace App\Services;

use App\Models\RealEstate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class PropertyMatcher
{
    protected array $weights;
    protected array $flexibleFields;
    protected array $realEstateTableColumns = ['price', 'type'];

    public function __construct()
    {
        $this->weights = config('properties.weights', []);
        $this->flexibleFields = config('properties.flexible_fields', []);
    }

    public function findMatches(
        array $userInput,
        int $limit = 10,
        int $minSimilarity = 50,
        int $strictMatchThreshold = 0
    ): Collection {
        $query = RealEstate::query()
            ->with(['properties', 'location', 'images', 'user']);

        $scoreCalculations = [];
        $scoreBindings = [];

        $matchCountCalculations = [];
        $matchCountBindings = [];

        $totalPossibleScore = 0;
        $activeInputCount = 0;

        foreach ($userInput as $key => $value) {
            if (empty($value) || !isset($this->weights[$key])) {
                continue;
            }

            $weight = $this->weights[$key];
            $totalPossibleScore += $weight;
            $activeInputCount++;

            $column = '';
            if (in_array($key, $this->realEstateTableColumns)) {
                $column = "real_estates.{$key}";
            } else {
                $column = "IFNULL(real_estate_properties.{$key}, 0)";
            }


            if (isset($this->flexibleFields[$key]) && is_numeric($value)) {
                $range = $this->flexibleFields[$key]['range'];
                $weightReductionFactor = $this->flexibleFields[$key]['weight_reduction_factor'];

                $scoreCalculations[] = "
                    (CASE
                        WHEN ABS({$column} - ?) <= ?
                        THEN {$weight} * (1 - (ABS({$column} - ?) / ?)) * (1 - {$weightReductionFactor})
                        ELSE 0
                    END)
                ";
                $scoreBindings = array_merge($scoreBindings, [$value, $range, $value, $range]);

                $matchCountCalculations[] = "(CASE WHEN ABS({$column} - ?) <= ? THEN 1 ELSE 0 END)";
                $matchCountBindings = array_merge($matchCountBindings, [$value, $range]);
            } else {
                // For exact matches (including 'type')
                $scoreCalculations[] = "(CASE WHEN {$column} = ? THEN {$weight} ELSE 0 END)";
                $scoreBindings[] = $value;

                $matchCountCalculations[] = "(CASE WHEN {$column} = ? THEN 1 ELSE 0 END)";
                $matchCountBindings[] = $value;
            }
        }

        if (empty($scoreCalculations) || $totalPossibleScore === 0) {
            return new Collection();
        }

        $query->leftJoin('real_estate_properties', 'real_estates.id', '=', 'real_estate_properties.real_estate_id');

        $scoreRaw = implode(' + ', $scoreCalculations);
        $similarityRaw = "ROUND((({$scoreRaw}) / {$totalPossibleScore}) * 100, 2)";

        $matchCountRaw = implode(' + ', $matchCountCalculations);

        $allBindings = array_merge(
            $scoreBindings,
            $matchCountBindings,
            $scoreBindings
        );

        try {
            $query->selectRaw(
                "real_estates.*,
                ({$scoreRaw}) AS score,
                ({$matchCountRaw}) AS match_count,
                {$similarityRaw} AS similarity",
                $allBindings
            );

            $query->having('similarity', '>=', $minSimilarity);

            if ($strictMatchThreshold > 0) {
                $query->having('match_count', '>=', $strictMatchThreshold);
            }

            $query->orderByDesc('match_count')
                ->orderByDesc('score');

            $query->groupBy('real_estates.id');

            $results = $query->limit($limit)->get();

            $results->transform(function ($realEstate) use ($userInput) {
                return [
                    'realEstate' => $realEstate,
                    'score' => (float) $realEstate->score,
                    'match_count' => (int) $realEstate->match_count,
                    'similarity' => (float) $realEstate->similarity,
                    'matched_features' => $this->getMatchedFeatures($realEstate, $userInput),
                ];
            });

            return $results;
        } catch (\Exception $e) {
            Log::error('PropertyMatcher Error:', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'user_input' => $userInput]);
            throw $e;
        }
    }

    private function getMatchedFeatures($realEstate, $userInput)
    {
        $features = [];
        if (!$realEstate) {
            return $features;
        }

        foreach ($userInput as $key => $value) {
            if (empty($value) || !isset($this->weights[$key])) continue;

            $propertyValue = null;
            if (in_array($key, $this->realEstateTableColumns)) {
                $propertyValue = $realEstate->$key;
            } elseif ($realEstate->properties && isset($realEstate->properties->$key)) {
                $propertyValue = $realEstate->properties->$key;
            }

            if ($propertyValue === null) {
                continue;
            }

            if (isset($this->flexibleFields[$key]) && is_numeric($value)) {
                $range = $this->flexibleFields[$key]['range'];
                $difference = abs($propertyValue - $value);

                if ($difference <= $range) {
                    $closeness = 1 - ($difference / $range);
                    $features[$key] = [
                        'value' => $propertyValue,
                        'user_input' => $value,
                        'match_type' => 'flexible',
                        'closeness' => round($closeness * 100) . '%',
                    ];
                }
            } else {
                if ($propertyValue == $value) {
                    $features[$key] = [
                        'value' => $propertyValue,
                        'user_input' => $value,
                        'match_type' => 'exact',
                    ];
                }
            }
        }
        return $features;
    }
}

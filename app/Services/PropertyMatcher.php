<?php

namespace App\Services;

use App\Models\RealEstate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

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

        foreach ($userInput as $key => $value) {
            if (! isset($value) || $value === '' || ! isset($this->weights[$key])) {
                continue;
            }

            $weight = $this->weights[$key];
            $totalPossibleScore += $weight;

            $column = in_array($key, $this->realEstateTableColumns)
                ? "real_estates.{$key}"
                : "real_estate_properties.{$key}";

            $nullCheck = in_array($key, $this->realEstateTableColumns)
                ? '' // No null check for main table columns
                : "{$column} IS NOT NULL AND";

            if (isset($this->flexibleFields[$key]) && is_numeric($value)) {
                $range = $this->flexibleFields[$key]['range'];
                $weightReductionFactor = $this->flexibleFields[$key]['weight_reduction_factor'];

                $scoreCalculations[] = "
                    (CASE WHEN {$nullCheck} ABS({$column} - ?) <= ?
                          THEN ? * (1 - (ABS({$column} - ?) / ?)) * (1 - ?)
                          ELSE 0 END)";
                $scoreBindings = array_merge($scoreBindings, [$value, $range, $weight, $value, $range, $weightReductionFactor]);

                $matchCountCalculations[] = "(CASE WHEN {$nullCheck} ABS({$column} - ?) <= ? THEN 1 ELSE 0 END)";
                $matchCountBindings = array_merge($matchCountBindings, [$value, $range]);
            } else {
                $scoreCalculations[] = "(CASE WHEN {$nullCheck} {$column} = ? THEN ? ELSE 0 END)";
                $scoreBindings = array_merge($scoreBindings, [$value, $weight]);

                $matchCountCalculations[] = "(CASE WHEN {$nullCheck} {$column} = ? THEN 1 ELSE 0 END)";
                $matchCountBindings[] = $value;
            }
        }

        if (empty($scoreCalculations) || $totalPossibleScore === 0) {
            return new Collection;
        }

        $query->leftJoin('real_estate_properties', 'real_estates.id', '=', 'real_estate_properties.real_estate_id');

        $scoreRaw = implode(' + ', $scoreCalculations);
        $matchCountRaw = implode(' + ', $matchCountCalculations);
        $similarityRaw = "ROUND((({$scoreRaw}) / {$totalPossibleScore}) * 100, 2)";

        // *** THE CRITICAL FIX IS HERE ***
        // We must supply bindings for every placeholder in the final select string.
        // The order is: 1. score's placeholders, 2. match_count's, 3. similarity's (which is a repeat of score's).
        $allSelectBindings = array_merge(
            $scoreBindings,
            $matchCountBindings,
            $scoreBindings // This duplication is necessary because $scoreRaw is used twice
        );

        try {
            $query->selectRaw(
                "real_estates.*,
                ({$scoreRaw}) AS score,
                ({$matchCountRaw}) AS match_count,
                {$similarityRaw} AS similarity",
                $allSelectBindings
            );

            // The having clause bindings are handled separately and correctly by Laravel.
            $query->having('similarity', '>=', $minSimilarity);

            if ($strictMatchThreshold > 0) {
                $query->having('match_count', '>=', $strictMatchThreshold);
            }

            $query->orderByDesc('score')
                ->orderByDesc('match_count');

            $query->groupBy('real_estates.id');

            $results = $query->limit($limit)->get();

            // The transformation logic seems fine, no changes needed here
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
            Log::error('PropertyMatcher Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_input' => $userInput,
            ]);
            throw $e;
        }
    }

    private function getMatchedFeatures($realEstate, $userInput)
    {
        $features = [];
        if (! $realEstate) {
            return $features;
        }

        foreach ($userInput as $key => $value) {
            if (empty($value) || ! isset($this->weights[$key])) {
                continue;
            }

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
                        'closeness' => round($closeness * 100).'%',
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

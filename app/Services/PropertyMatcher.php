<?php

namespace App\Services;

use App\Models\RealEstate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyMatcher
{
    protected array $weights;
    protected array $flexibleFields;

    public function __construct()
    {
        $this->weights = config('properties.weights', []);
        $this->flexibleFields = config('properties.flexible_fields', []);
    }

    public function findMatches(array $userInput, int $limit = 10, int $minSimilarity = 50)
    {
        $query = RealEstate::query()
            ->with(['properties', 'location', 'images', 'user']);

        $scoreCalculations = [];
        $scoreBindings = [];

        // NEW: We will now also calculate the number of matched criteria.
        $matchCountCalculations = [];
        $matchCountBindings = [];

        $totalPossibleScore = 0;

        foreach ($userInput as $key => $value) {
            if (empty($value) || !isset($this->weights[$key])) {
                continue;
            }

            $weight = $this->weights[$key];
            $totalPossibleScore += $weight;

            if ($key === 'price') {
                $column = 'real_estates.price';
            } else {
                $column = "IFNULL(real_estate_properties.{$key}, 0)";
            }

            if (isset($this->flexibleFields[$key]) && is_numeric($value)) {
                $range = $this->flexibleFields[$key]['range'];
                $weightReductionFactor = $this->flexibleFields[$key]['weight_reduction_factor'];

                // Calculation for the weighted score
                $scoreCalculations[] = "
                    (CASE
                        WHEN ABS({$column} - ?) <= ?
                        THEN {$weight} * (1 - (ABS({$column} - ?) / ?)) * (1 - {$weightReductionFactor})
                        ELSE 0
                    END)
                ";
                $scoreBindings = array_merge($scoreBindings, [$value, $range, $value, $range]);

                // NEW: Calculation for the match count (1 if it matches, 0 if not)
                $matchCountCalculations[] = "(CASE WHEN ABS({$column} - ?) <= ? THEN 1 ELSE 0 END)";
                $matchCountBindings = array_merge($matchCountBindings, [$value, $range]);
            } else {
                // Calculation for the weighted score
                $scoreCalculations[] = "(CASE WHEN {$column} = ? THEN {$weight} ELSE 0 END)";
                $scoreBindings[] = $value;

                // NEW: Calculation for the match count
                $matchCountCalculations[] = "(CASE WHEN {$column} = ? THEN 1 ELSE 0 END)";
                $matchCountBindings[] = $value;
            }
        }

        if (empty($scoreCalculations) || $totalPossibleScore === 0) {
            return RealEstate::inRandomOrder()->limit($limit)->with(['properties', 'location', 'images', 'user'])->get();
        }

        $query->leftJoin('real_estate_properties', 'real_estates.id', '=', 'real_estate_properties.real_estate_id');

        $scoreRaw = implode(' + ', $scoreCalculations);
        $similarityRaw = "ROUND((({$scoreRaw}) / {$totalPossibleScore}) * 100, 2)";

        // NEW: Create the raw SQL for the match count
        $matchCountRaw = implode(' + ', $matchCountCalculations);

        // Combine all bindings in the correct order for the final query
        $allBindings = array_merge(
            $scoreBindings,         // For the score calculation
            $matchCountBindings,    // For the new match_count calculation
            $scoreBindings          // Again for the similarity calculation
        );

        try {
            // NEW: Added `match_count` to the SELECT statement
            $query->selectRaw(
                "real_estates.*, 
                ({$scoreRaw}) AS score, 
                ({$matchCountRaw}) AS match_count, 
                {$similarityRaw} AS similarity",
                $allBindings
            );

            $query->having('similarity', '>=', $minSimilarity);

            // THE KEY CHANGE IS HERE: Order by match_count first, then by score.
            $query->orderByDesc('match_count')
                ->orderByDesc('score');

            $query->groupBy('real_estates.id');

            $results = $query->limit($limit)->get();

            return $results->map(function ($realEstate) use ($userInput) {
                return [
                    'realEstate' => $realEstate,
                    'score' => (float) $realEstate->score,
                    'match_count' => (int) $realEstate->match_count, // Added match count to output
                    'similarity' => (float) $realEstate->similarity,
                    'matched_features' => $this->getMatchedFeatures($realEstate, $userInput),
                ];
            });
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
            if ($key === 'price') {
                $propertyValue = $realEstate->price;
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

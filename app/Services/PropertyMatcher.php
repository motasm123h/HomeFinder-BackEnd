<?php

namespace App\Services;

use App\Models\RealEstate_properties;
use App\Models\RealEstate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // For debugging

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

        $query->join('real_estate_properties', 'real_estates.id', '=', 'real_estate_properties.real_estate_id');

        $scoreCalculations = [];
        $totalPossibleScore = 0;

        foreach ($userInput as $key => $value) {
            $column = ($key === 'price') ? 'real_estates.price' : 'real_estate_properties.' . $key;

            if (!isset($this->weights[$key])) {
                continue;
            }

            $weight = $this->weights[$key];
            $totalPossibleScore += $weight;

            if (isset($this->flexibleFields[$key]) && is_numeric($value)) {
                $range = $this->flexibleFields[$key]['range'];
                $weightReductionFactor = $this->flexibleFields[$key]['weight_reduction_factor'];

                $query->where(function (Builder $q) use ($column, $value, $range) {
                    $q->whereBetween($column, [$value - $range, $value + $range]);
                });

                $scoreCalculations[] = "
                    (CASE
                        WHEN ABS({$column} - {$value}) <= {$range}
                        THEN {$weight} * (1 - (ABS({$column} - {$value}) / {$range})) * (1 - {$weightReductionFactor})
                        ELSE 0
                    END)
                ";
            } else {
                // Direct match for enum/boolean fields
                // Ensure $value is properly quoted for direct matches to prevent SQL injection and errors
                $quotedValue = DB::connection()->getPdo()->quote($value);
                $scoreCalculations[] = "
                    (CASE
                        WHEN real_estate_properties.$key = {$quotedValue}
                        THEN $weight
                        ELSE 0
                    END)
                ";
                // Add where clause for direct matches to narrow down results
                $query->where("real_estate_properties.$key", $value);
            }
        }

        // If no search criteria were provided, return a random set of RealEstate
        // This acts as a fallback or default display.
        if (empty($scoreCalculations)) {
            // Use with('properties') here to ensure relations are loaded for random results too
            return RealEstate::inRandomOrder()->limit($limit)->with(['properties', 'location', 'images', 'user'])->get();
        }

        $scoreRaw = implode(' + ', $scoreCalculations);

        try {
            // Select all columns from real_estates table and the calculated score
            // The `real_estates.*` is crucial to retrieve all original columns for the model
            $query->selectRaw("real_estates.*, {$scoreRaw} AS score");

            $query->orderByDesc('score');

            $results = $query->limit($limit)->get();

            return $results->map(function ($realEstate) use ($userInput, $totalPossibleScore) {
                $score = $realEstate->score;
                $similarity = $totalPossibleScore > 0 ? round(($score / $totalPossibleScore) * 100, 2) : 0;

                // Pass the full RealEstate model to getMatchedFeatures, as it contains 'properties'
                $matchedFeatures = $this->getMatchedFeatures($realEstate, $userInput);

                return [
                    'realEstate' => $realEstate,
                    'score' => $score,
                    'similarity' => $similarity,
                    'matched_features' => $matchedFeatures,
                ];
            })->filter(fn($item) => $item['similarity'] >= $minSimilarity);
        } catch (\Exception $e) {
            \Log::error('PropertyMatcher Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'score_raw_sql' => $scoreRaw ?? 'not generated',
                'user_input' => $userInput
            ]);
            throw $e; // Re-throw to ensure it's caught by your global handler in bootstrap/app.php
        }
    }

    /**
     * Helper to determine which features matched and how.
     * Accepts the RealEstate model (which contains the 'properties' relation).
     */
    private function getMatchedFeatures($realEstate, $userInput)
    {
        $features = [];
        if (!$realEstate) {
            return $features;
        }

        foreach ($userInput as $key => $value) {
            if (!isset($this->weights[$key])) continue;

            $propertyValue = null;
            // Check if the key is 'price' (on RealEstate model itself) or on 'properties' relation
            if ($key === 'price') {
                $propertyValue = $realEstate->price;
            } elseif ($realEstate->properties && isset($realEstate->properties->$key)) {
                $propertyValue = $realEstate->properties->$key;
            }

            if ($propertyValue === null) {
                continue; // Skip if property value doesn't exist on either model
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
                } else {
                    $features[$key] = [
                        'value' => $propertyValue,
                        'user_input' => $value,
                        'match_type' => 'out_of_range',
                    ];
                }
            } else {
                // Direct match for enums/booleans
                if ($propertyValue == $value) {
                    $features[$key] = [
                        'value' => $propertyValue,
                        'user_input' => $value,
                        'match_type' => 'exact',
                    ];
                } else {
                    $features[$key] = [
                        'value' => $propertyValue,
                        'user_input' => $value,
                        'match_type' => 'no_match',
                    ];
                }
            }
        }

        return $features;
    }
}

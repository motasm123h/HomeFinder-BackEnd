<?php

namespace App\Services;

use App\Models\RealEstate_properties;

class PropertyMatcher
{
    protected $weights;
    protected $flexibleFields; // Fields that can have flexible matching

    public function __construct()
    {
        $this->weights = config('properties.weights');
        
        // Define which fields should use flexible matching
        $this->flexibleFields = [
            'room_no' => [
                'range' => 1, // ±1 room difference
                'weight_reduction' => 0.5 // Reduce weight by 50% for partial matches
            ],
            'space_status' => [
                'range' => 0.1, // ±10% difference
                'weight_reduction' => 0.3
            ],
            'floor' => [
                'range' => 1,
                'weight_reduction' => 0.5
            ]
        ];
    }

    
    public function findMatches(array $userInput, int $limit = 10)
    {
        $properties = RealEstate_properties::with('realEstate')->get();

        return $properties->map(function ($property) use ($userInput) {
            $score = $this->calculateScore($property, $userInput);
            
            return [
                'property' => $property,
                'score' => $score,
                'similarity' => $this->calculateSimilarityPercentage($score, $userInput),
                'matched_features' => $this->getMatchedFeatures($property, $userInput)
            ];
        })->sortByDesc('score')->take($limit);
    }

    /**
     * Calculate matching score for a single property
     */
    private function calculateScore($property, $userInput)
    {
        $score = 0;
        
        foreach ($userInput as $key => $value) {
            if (!isset($this->weights[$key])) continue;
            
            if (isset($this->flexibleFields[$key]) && is_numeric($value)) {
                // Handle flexible numeric fields (rooms, space, etc.)
                $score += $this->calculateFlexibleMatch(
                    $property->$key,
                    $value,
                    $this->weights[$key],
                    $this->flexibleFields[$key]
                );
            } elseif ($property->$key == $value) {
                // Exact match for non-flexible fields
                $score += $this->weights[$key];
            }
        }
        
        return $score;
    }

    /**
     * Calculate partial matches for numeric fields
     */
    private function calculateFlexibleMatch($propertyValue, $userValue, $fullWeight, $options)
    {
        $difference = abs($propertyValue - $userValue);
        $range = $options['range'];
        
        // If within acceptable range
        if ($difference <= $range) {
            // Calculate how close it is (1 = perfect match, 0 = edge of range)
            $closeness = 1 - ($difference / $range);
            
            // Apply weight reduction for partial matches
            return $fullWeight * $closeness * (1 - $options['weight_reduction']);
        }
        
        return 0; // No match if outside range
    }

    /**
     * Calculate similarity percentage
     */
    private function calculateSimilarityPercentage($score, $userInput)
    {
        $relevantWeights = array_intersect_key($this->weights, $userInput);
        $totalPossibleScore = array_sum($relevantWeights);
        
        return $totalPossibleScore > 0 ? round(($score / $totalPossibleScore) * 100, 2) : 0;
    }

    /**
     * Get list of matched features with their scores
     */
    private function getMatchedFeatures($property, $userInput)
    {
        $features = [];
        
        foreach ($userInput as $key => $value) {
            if (!isset($this->weights[$key])) continue;
            
            if (isset($this->flexibleFields[$key])) {
                $features[$key] = $this->calculateFlexibleMatch(
                    $property->$key,
                    $value,
                    $this->weights[$key],
                    $this->flexibleFields[$key]
                );
            } else {
                $features[$key] = ($property->$key == $value) ? $this->weights[$key] : 0;
            }
        }
        
        return $features;
    }
}
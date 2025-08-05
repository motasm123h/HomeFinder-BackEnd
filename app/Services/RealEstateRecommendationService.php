<?php

namespace App\Services;

use App\Models\RealEstate;
use App\Models\CustomerPreference; // Ensure this is App\Models\CustomerPreference, not CustomerPreferences
use Illuminate\Database\Eloquent\Collection;

class RealEstateRecommendationService
{
    // Define weights for each preference type (you can adjust these)
    private const PREFERENCE_WEIGHTS = [
        'type' => 20, // e.g., rental/sale
        'kind' => 25, // e.g., apartment/villa
        'price_match' => 40, // Being within price range is highly valued
        'price_nearness' => 20, // Closeness to midpoint of price range (if using numeric range)
        'room_no_match' => 15, // Meeting minimum rooms
        'space_status_match' => 15, // Meeting minimum space
        'electricity_status' => 10,
        'water_status' => 10,
        'transportation_status' => 10,
        'water_well' => 20,
        'solar_energy' => 30,
        'garage' => 20,
        'elevator' => 15,
        'floor_preference' => 8, // Bonus for lower floors (adjust logic if needed)
        'garden_status' => 20,
        'attired' => 10,
        'ownership_type' => 10,
        'location_match' => 50, // Matching city/district is crucial
        'real_estate_total_weight' => 10, // From RealEstate model's own weight
        'properties_total_weight' => 10, // From RealEstate_properties model's own weight
    ];

    // Maps for normalization (should mirror those in ComparisonController for consistency)
    private const TYPE_MAP = [
        'rental' => 'For Rent', 'sale' => 'For Sale', 'للبيع' => 'For Sale', 'للأيجار' => 'For Rent',
    ];
    private const KIND_MAP = [
        'apartment' => 'Apartment', 'villa' => 'Villa', 'chalet' => 'Chalet', 'شقة' => 'Apartment', 'فيلا' => 'Villa', 'شاليه' => 'Chalet',
    ];
    private const QUALITY_STATUS_MAP = [
        '1' => 'Good', '2' => 'Average', '3' => 'Bad',
    ];
    private const YES_NO_MAP = [ // For water_well, solar_energy, garage, elevator, garden_status
        '1' => true, '2' => false,
    ];
    private const ATTIRED_MAP = [
        '1' => 'Fully Furnished/Well-Maintained', '2' => 'Partially Furnished/Average-Maintained', '3' => 'Not Furnished/Poorly-Maintained',
    ];
    private const OWNERSHIP_TYPE_MAP = [
        'green' => 'Green Ownership', 'court' => 'Court Ownership',
        'Green' => 'Green Ownership', 'Court' => 'Court Ownership', // Ensure consistency with DB storage
    ];


    /**
     * Get recommendations for a given customer preference.
     *
     * @param CustomerPreference $customerPreference The customer's preference model. // Corrected type hint
     * @param int $limit The maximum number of recommendations to return.
     * @param array $filters Additional filters for RealEstate query (e.g., status, hidden).
     * @return Collection
     */
    public function getRecommendations(CustomerPreference $customerPreference, int $limit = 10, array $filters = []): Collection
    {
        // Start with all active, non-hidden real estates
        $query = RealEstate::query()
            ->with(['properties', 'location'])
            ->where('status', 'open')
            ->where('hidden', 0);

        // Apply additional filters from the request
        if (!empty($filters['real_estate_type'])) {
            $query->where('type', $filters['real_estate_type']);
        }
        if (!empty($filters['real_estate_kind'])) {
            $query->where('kind', $filters['real_estate_kind']);
        }
        // Add other direct filters here as needed

        $realEstates = $query->get();

        // Calculate scores for each real estate
        $scoredRealEstates = $realEstates->map(function (RealEstate $realEstate) use ($customerPreference) {
            $score = $this->calculateMatchScore($realEstate, $customerPreference);
            $realEstate->match_score = $score; // Add score as a dynamic property
            return $realEstate;
        });

        // Sort by score in descending order and limit
        return $scoredRealEstates->sortByDesc('match_score')->take($limit);
    }

    /**
     * Calculates a match score for a single RealEstate against a CustomerPreference.
     *
     * @param RealEstate $realEstate
     * @param CustomerPreference $customerPreference // Corrected type hint
     * @return int
     */
    private function calculateMatchScore(RealEstate $realEstate, CustomerPreference $customerPreference): int
    {
        $score = 0;

        // 1. Basic RealEstate Weights (from RealEstate model itself)
        $score += ($realEstate->total_weight ?? 0) * (self::PREFERENCE_WEIGHTS['real_estate_total_weight'] / 10);

        // 2. Location Match (High Priority)
        // Ensure customer preference has city and district before trying to access
        if ($realEstate->location && ($customerPreference->city ?? null) && ($customerPreference->district ?? null)) {
            if (
                strtolower($realEstate->location->city) === strtolower($customerPreference->city) &&
                strtolower($realEstate->location->district) === strtolower($customerPreference->district)
            ) {
                $score += self::PREFERENCE_WEIGHTS['location_match'];
            } elseif (strtolower($realEstate->location->city) === strtolower($customerPreference->city)) {
                $score += self::PREFERENCE_WEIGHTS['location_match'] / 2; // Half bonus for city match only
            } else {
                $score -= self::PREFERENCE_WEIGHTS['location_match'] / 4; // Penalty if location is completely off
            }
        } else {
             // If customer preference location data is missing, don't penalize, but don't bonus either.
             // You could add a small penalty here if *any* missing preference data is considered bad.
        }


        // 3. Type Match (Rental/Sale) - THIS IS CRITICAL TO CHECK AFTER ADDING TO CUSTOMER_PREFERENCES TABLE
        if (($customerPreference->type ?? null) !== null) { // Only score if preference is set
            $normalizedRealEstateType = self::TYPE_MAP[$realEstate->type] ?? null;
            $normalizedCustomerType = self::TYPE_MAP[$customerPreference->type] ?? null;
            if ($normalizedRealEstateType && $normalizedCustomerType && strtolower($normalizedRealEstateType) === strtolower($normalizedCustomerType)) {
                $score += self::PREFERENCE_WEIGHTS['type'];
            } else {
                $score -= self::PREFERENCE_WEIGHTS['type'] / 2;
            }
        }


        // 4. Kind Match (Apartment/Villa/Chalet) - THIS IS CRITICAL TO CHECK AFTER ADDING TO CUSTOMER_PREFERENCES TABLE
        if (($customerPreference->kind ?? null) !== null) { // Only score if preference is set
            $normalizedRealEstateKind = self::KIND_MAP[$realEstate->kind] ?? null;
            $normalizedCustomerKind = self::KIND_MAP[$customerPreference->kind] ?? null;
            if ($normalizedRealEstateKind && $normalizedCustomerKind && strtolower($normalizedRealEstateKind) === strtolower($normalizedCustomerKind)) {
                $score += self::PREFERENCE_WEIGHTS['kind'];
            } else {
                $score -= self::PREFERENCE_WEIGHTS['kind'] / 2;
            }
        }


        // 5. Price Match (Crucial)
        // Re-iterating: 'price' as enum '1','2','3' in customer_preferences is limiting.
        // Assuming realEstate->price is numeric and customerPreference->price is the enum.
        if (($customerPreference->price ?? null) !== null) {
            $priceCategoryRealEstate = $this->getPriceCategory($realEstate->price);
            $priceCategoryPreference = $customerPreference->price;

            if ($priceCategoryRealEstate === $priceCategoryPreference) {
                $score += self::PREFERENCE_WEIGHTS['price_match'];
            } elseif (abs((int)$priceCategoryRealEstate - (int)$priceCategoryPreference) === 1) {
                $score += self::PREFERENCE_WEIGHTS['price_match'] / 2; // Partial match if one category off
            } else {
                $score -= self::PREFERENCE_WEIGHTS['price_match'] / 4; // Penalty
            }
        }


        // 6. Properties Details (if available)
        if ($realEstate->properties) {
            $properties = $realEstate->properties;

            // Rooms
            if (($customerPreference->room_no ?? null) !== null) { // Only score if preference is set
                if (($properties->room_no ?? 0) >= ($customerPreference->room_no)) {
                    $score += self::PREFERENCE_WEIGHTS['room_no_match'];
                    if (($properties->room_no - $customerPreference->room_no) >= 2) {
                        $score += 5;
                    }
                } else {
                    $score -= self::PREFERENCE_WEIGHTS['room_no_match'] / 2;
                }
            } else {
                // If no preference for rooms, still give a bonus for more rooms (general desirability)
                $score += min(($properties->room_no ?? 0) * 2, 10); // Max 10 bonus for rooms
            }


            // Space Status
            if (($customerPreference->space_status ?? null) !== null) { // Only score if preference is set
                if (($properties->space_status ?? 0) >= ($customerPreference->space_status)) {
                    $score += self::PREFERENCE_WEIGHTS['space_status_match'];
                    if (($properties->space_status - $customerPreference->space_status) >= 50) {
                        $score += 5;
                    }
                } else {
                    $score -= self::PREFERENCE_WEIGHTS['space_status_match'] / 2;
                }
            } else {
                // If no preference for space, still give a bonus for more space (general desirability)
                $score += min(($properties->space_status ?? 0) / 10, 10); // Max 10 bonus for space (e.g., 100sqm = 10 bonus)
            }


            // Quality Status fields (Electricity, Water, Transportation, Attired)
            $score += $this->getQualityMatchScore(
                $properties->electricity_status,
                $customerPreference->electricity_status,
                self::PREFERENCE_WEIGHTS['electricity_status']
            );
            $score += $this->getQualityMatchScore(
                $properties->water_status,
                $customerPreference->water_status,
                self::PREFERENCE_WEIGHTS['water_status']
            );
            $score += $this->getQualityMatchScore(
                $properties->transportation_status,
                $customerPreference->transportation_status,
                self::PREFERENCE_WEIGHTS['transportation_status']
            );
            $score += $this->getQualityMatchScore(
                $properties->attired,
                $customerPreference->attired,
                self::PREFERENCE_WEIGHTS['attired']
            );


            // Boolean preferences (water_well, solar_energy, garage, elevator, garden_status)
            $score += $this->getBooleanMatchScore(
                $properties->water_well,
                $customerPreference->water_well,
                self::PREFERENCE_WEIGHTS['water_well']
            );
            $score += $this->getBooleanMatchScore(
                $properties->solar_energy,
                $customerPreference->solar_energy,
                self::PREFERENCE_WEIGHTS['solar_energy']
            );
            $score += $this->getBooleanMatchScore(
                $properties->garage,
                $customerPreference->garage,
                self::PREFERENCE_WEIGHTS['garage']
            );
            $score += $this->getBooleanMatchScore(
                $properties->elevator,
                $customerPreference->elevator,
                self::PREFERENCE_WEIGHTS['elevator']
            );
            $score += $this->getBooleanMatchScore(
                $properties->garden_status,
                $customerPreference->garden_status,
                self::PREFERENCE_WEIGHTS['garden_status']
            );

            // Floor preference (assuming lower floors are generally preferred up to a certain point)
            // If customer wants a specific floor (not 0) and property is at or below it (and not 0)
            if (($customerPreference->floor ?? null) !== null) { // Only score if preference is set
                if (($customerPreference->floor > 0 && ($properties->floor ?? 0) <= $customerPreference->floor && ($properties->floor ?? 0) > 0) ||
                    ($customerPreference->floor == 0 && ($properties->floor ?? 0) == 0)) { // Customer wants ground floor AND property is ground floor
                    $score += self::PREFERENCE_WEIGHTS['floor_preference'];
                } else {
                    $score -= self::PREFERENCE_WEIGHTS['floor_preference'] / 2;
                }
            } else {
                // If no preference, give a small bonus for reasonable floors (e.g., 1-5)
                if (($properties->floor ?? 0) > 0 && ($properties->floor ?? 0) <= 5) {
                    $score += self::PREFERENCE_WEIGHTS['floor_preference'] / 2;
                }
            }


            // Direction (assuming '1' is most favorable)
            if (($customerPreference->direction ?? null) !== null) { // Only score if preference is set
                if (($properties->direction ?? null) === $customerPreference->direction) {
                    $score += 8; // Small bonus for exact direction match
                } elseif (($properties->direction ?? null) == '1') { // Property has good direction regardless of exact match
                    $score += 3;
                } else {
                    $score -= 3; // Slight penalty for less favorable direction
                }
            } else {
                // If no preference, generally prefer '1'
                if (($properties->direction ?? null) == '1') {
                    $score += 5;
                } elseif (($properties->direction ?? null) == '3') {
                    $score -= 2;
                }
            }


            // Ownership Type
            if (($customerPreference->ownership_type ?? null) !== null) { // Only score if preference is set
                $normalizedRealEstateOwnership = self::OWNERSHIP_TYPE_MAP[$properties->ownership_type] ?? null;
                $normalizedCustomerOwnership = self::OWNERSHIP_TYPE_MAP[$customerPreference->ownership_type] ?? null;

                if ($normalizedRealEstateOwnership && $normalizedCustomerOwnership && strtolower($normalizedRealEstateOwnership) === strtolower($normalizedCustomerOwnership)) {
                    $score += self::PREFERENCE_WEIGHTS['ownership_type'];
                } else {
                    $score -= self::PREFERENCE_WEIGHTS['ownership_type'] / 2;
                }
            }


            // RealEstate_properties total_weight
            $score += ($properties->total_weight ?? 0) * (self::PREFERENCE_WEIGHTS['properties_total_weight'] / 10);
        }

        // Ensure score doesn't go below zero if you want to rank all properties positively
        // return max(0, $score);
        return $score;
    }

    /**
     * Helper for boolean preferences ('1'/'2' for Yes/No).
     *
     * @param string|null $realEstateValue The property's value ('1' or '2').
     * @param string|null $preferenceValue The customer's preference ('1' or '2').
     * @param int $weight The base weight for this preference.
     * @return int
     */
    private function getBooleanMatchScore(?string $realEstateValue, ?string $preferenceValue, int $weight): int
    {
        if ($preferenceValue === null) {
            // If no preference, but property has it (e.g., solar, garage), give a small default bonus
            if (($realEstateValue ?? null) == '1') {
                return $weight / 4; // Small bonus for generally good features
            }
            return 0; // No preference, and property doesn't have it, no score change
        }

        $realEstateBool = self::YES_NO_MAP[$realEstateValue] ?? null;
        $preferenceBool = self::YES_NO_MAP[$preferenceValue] ?? null;

        if ($realEstateBool === null) { // Real estate data is missing
            return -$weight / 4; // Small penalty for unknown status if preference exists
        }

        if ($realEstateBool === $preferenceBool && $preferenceBool === true) {
            return $weight; // User wants it and property has it
        } elseif ($realEstateBool === $preferenceBool && $preferenceBool === false) {
            return $weight / 2; // User doesn't want it and property doesn't have it (good match)
        } elseif ($realEstateBool === true && $preferenceBool === false) {
            return -$weight; // User doesn't want it but property has it (strong penalty)
        } elseif ($realEstateBool === false && $preferenceBool === true) {
            return -$weight; // User wants it but property doesn't have it (strong penalty)
        }
        return 0;
    }

    /**
     * Helper for quality status preferences ('1'/'2'/'3').
     *
     * @param string|null $realEstateValue The property's value ('1', '2', or '3').
     * @param string|null $preferenceValue The customer's preference ('1', '2', or '3').
     * @param int $weight The base weight for this preference.
     * @return int
     */
    private function getQualityMatchScore(?string $realEstateValue, ?string $preferenceValue, int $weight): int
    {
        if ($preferenceValue === null) {
            // If no specific preference, give a default bonus based on property's quality (1=good, 2=average, 3=bad)
            switch ($realEstateValue) {
                case '1': return $weight;
                case '2': return $weight / 2;
                case '3': return -$weight; // Stronger penalty if it's bad and no preference
                default: return 0;
            }
        }

        if ($realEstateValue === null) { // Real estate data is missing
            return -$weight / 2; // Penalty for unknown status if preference exists
        }

        if ($realEstateValue === $preferenceValue) {
            return $weight; // Exact match
        }

        $reValue = (int)$realEstateValue;
        $prefValue = (int)$preferenceValue;

        // If property quality is better or same as desired:
        if ($reValue <= $prefValue) { // e.g., RE is '1' (Good), Pref is '2' (Average) or '1' (Good)
            return $weight; // Full bonus if property is good enough or better
        } elseif ($reValue > $prefValue) { // Property quality is worse than preferred
            return -$weight; // Strong penalty if worse
        }

        return 0;
    }

    /**
     * Helper to categorize numeric price into enum categories.
     * This is a placeholder and depends on your actual price ranges.
     *
     * @param float|int $price
     * @return string
     */
    private function getPriceCategory($price): string
    {
        // Define your actual price thresholds here based on your business logic.
        // These should correspond to what your 'price' enum values '1', '2', '3' represent.
        if ($price < 10000000) return '1'; // Example: Less than 10 million (e.g., small apartments)
        if ($price < 50000000) return '2'; // Example: 10 million to 50 million (e.g., medium apartments/small villas)
        return '3'; // Example: 50 million and above (e.g., large villas, chalets)
    }
}
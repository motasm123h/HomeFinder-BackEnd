<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SearchService;
use App\Models\RealEstate;
use App\Models\RealEstate_Location;
use App\Services\PropertyMatcher;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log; // For logging

class SearchController extends Controller
{
    use ResponseTrait;

    protected PropertyMatcher $propertyMatcher;
    public function __construct(private SearchService $searchService, PropertyMatcher $propertyMatcher)
    {
        $this->propertyMatcher = $propertyMatcher;
    }
    public function mostSearched()
    {
        $results = $this->searchService->getMostSearchedRealEstates();
        return response()->json($results);
    }


    public function mostWatch()
    {
        $results = $this->searchService->getMostWatchedRealEstates();
        return response()->json($results);
    }

    public function preferences(Request $request)
    {
        $validated = $request->validate([
            'room_no' => 'sometimes|integer|min:0',
            'price' => 'sometimes|numeric|min:0',
            'space_status' => 'sometimes|integer|min:0',
            'electricity_status' => 'sometimes|in:1,2,3',
            'water_status' => 'sometimes|in:1,2,3',
            'transportation_status' => 'sometimes|in:1,2,3',
            'water_well' => 'sometimes|in:1,2',
            'solar_energy' => 'sometimes|in:1,2',
            'garage' => 'sometimes|in:1,2',
            'direction' => 'sometimes|in:1,2,3',
            'elevator' => 'sometimes|in:1,2',
            'floor' => 'sometimes|integer',
            'garden_status' => 'sometimes|in:1,2',
            'attired' => 'sometimes|in:1,2,3',
            'ownership_type' => 'sometimes|in:green,court',
            'type' => 'sometimes|in:sale,rental',
            'limit' => 'sometimes|integer|min:1|max:100',
            'min_similarity' => 'sometimes|integer|min:0|max:100',
            'strict_match_threshold' => 'sometimes|integer|min:0',
        ]);

        $limit = $validated['limit'] ?? 10;
        $minSimilarity = $validated['min_similarity'] ?? 60;
        $strictMatchThreshold = $validated['strict_match_threshold'] ?? 1;

        $actualUserInputCount = 0;
        foreach ($validated as $key => $value) {
            if (empty($value) || !isset($this->propertyMatcher->weights[$key]) || in_array($key, ['limit', 'min_similarity', 'strict_match_threshold'])) {
                continue;
            }
            $actualUserInputCount++;
        }
        $strictMatchThreshold = min($strictMatchThreshold, $actualUserInputCount);


        $results = $this->propertyMatcher->findMatches(
            $validated,
            $limit,
            $minSimilarity,
            $strictMatchThreshold
        );

        return $this->apiResponse(
            'Preference-based real estates retrieved successfully',
            [
                'matches' => $results, 
                'searchInput' => $validated,
                'effectiveMinSimilarity' => $minSimilarity,
                'effectiveStrictMatchThreshold' => $strictMatchThreshold,
            ],
            200
        );
    }
    public function byLocation(Request $request) {}

    // public function search(Request $request)
    // {
    //     $query = trim($request->input('query'));

    //     if (!$query) {
    //         return response()->json([
    //             'message' => 'Empty search query.',
    //             'data' => [],
    //         ], 400);
    //     }

    //     $query = trim(preg_replace('/\s+/', ' ', $query));

    //     $query = rtrim($query, " \t\n\r\0\x0B.,،؛؟");

    //     \Log::info('Normalized search query:', ['query' => $query]);

    //     $locationIds = RealEstate_Location::where('district', 'like', '%' . $query . '%')->pluck('id');

    //     $results = RealEstate::with(['location', 'images', 'properties'])
    //         ->where(function ($q) use ($query, $locationIds) {
    //             $q->where('description', 'like', "%$query%")
    //             ->orWhereIn('real_estate_location_id', $locationIds)
    //             ->orWhereHas('properties', function ($q) use ($query) {
    //                 $q->where('direction', 'like', "%$query%");
    //             });
    //         })
    //         ->get();

    //     return response()->json([
    //         'data' => $results,
    //     ]);
    // }


    // public function search(Request $request)
    // {
    //     $query = trim($request->input('query'));

    //     if (!$query) {
    //         return response()->json([
    //             'message' => 'Empty search query.',
    //             'data' => [],
    //         ], 400);
    //     }

    //     $query = trim(preg_replace('/\s+/', ' ', $query));
    //     \Log::info('Befor Normalized search query:', ['query' => $query]);
    //     $query = rtrim($query, " \t\n\r\0\x0B.,،؛؟");
    //     \Log::info('Normalized search query:', ['query' => $query]);

    //     $searchType = $this->detectSearchIntent($query);
    //     \Log::info('Detected search intent:', ['type' => $searchType]);

    //     $results = RealEstate::with(['location', 'images', 'properties']);

    //     switch ($searchType) {
    //         case 'location':
    //             $locationQuery = $this->extractLocationQuery($query);
    //             $locationIds = RealEstate_Location::where('district', 'like', '%' . $locationQuery . '%')
    //                 ->pluck('id');
    //             $results->whereIn('real_estate_location_id', $locationIds);
    //             break;

    //         case 'price':
    //             $priceRange = $this->extractPriceRange($query);
    //             if ($priceRange['min']) {
    //                 $results->where('price', '>=', $priceRange['min']);
    //             }
    //             if ($priceRange['max']) {
    //                 $results->where('price', '<=', $priceRange['max']);
    //             }
    //             break;

    //         case 'type':
    //             $type = $this->extractType($query);
    //             $results->where('type', $type);
    //             break;

    //         default:
    //             $locationIds = RealEstate_Location::where('district', 'like', '%' . $query . '%')
    //                 ->pluck('id');
    //             $results->where(function ($q) use ($query, $locationIds) {
    //                 $q->where('description', 'like', "%$query%")
    //                     ->orWhereIn('real_estate_location_id', $locationIds)
    //                     ->orWhereHas('properties', function ($q) use ($query) {
    //                         $q->where('direction', 'like', "%$query%");
    //                     });
    //             });
    //     }

    //     return response()->json([
    //         'data' => $results->get(),
    //     ]);
    // }

    // protected function detectSearchIntent(string $query): string
    // {
    //     $locationKeywords = ['مكانه', 'يقع', 'يتوسط', 'داخل', 'بـ', 'في', 'منطقة', 'حي', 'قرب'];
    //     $priceKeywords = ['سعره', 'تكلفته', 'السعر', 'الثمن', 'بسعر', 'بقيمة', 'بحوالي'];
    //     $typeKeywords = ['إيجار', 'اجار', 'شراء', 'تمليك', 'للايجار', 'للبيع'];

    //     $query = mb_strtolower($query);

    //     foreach ($locationKeywords as $keyword) {
    //         if (str_contains($query, $keyword)) {
    //             return 'location';
    //         }
    //     }

    //     foreach ($priceKeywords as $keyword) {
    //         if (str_contains($query, $keyword)) {
    //             return 'price';
    //         }
    //     }

    //     foreach ($typeKeywords as $keyword) {
    //         if (str_contains($query, $keyword)) {
    //             return 'type';
    //         }
    //     }

    //     return 'general';
    // }

    // protected function extractLocationQuery(string $query): string
    // {
    //     $keywords = ['مكانه', 'يقع', 'يتوسط', 'داخل', 'بـ', 'في', 'منطقة', 'حي', 'قرب'];
    //     $query = str_replace($keywords, '', $query);
    //     return trim($query);
    // }

    // protected function extractPriceRange(string $query): array
    // {
    //     preg_match_all('/\d+/', $query, $matches);
    //     $numbers = $matches[0] ?? [];

    //     return [
    //         'min' => $numbers[0] ?? null,
    //         'max' => $numbers[1] ?? null,
    //     ];
    // }

    // protected function extractType(string $query): string
    // {
    //     $rentKeywords = ['إيجار', 'اجار', 'للايجار'];
    //     $buyKeywords = ['شراء', 'تمليك', 'للبيع'];

    //     $query = mb_strtolower($query);

    //     foreach ($rentKeywords as $keyword) {
    //         if (str_contains($query, $keyword)) {
    //             return 'rent';
    //         }
    //     }

    //     foreach ($buyKeywords as $keyword) {
    //         if (str_contains($query, $keyword)) {
    //             return 'buy';
    //         }
    //     }

    //     return 'any';
    // }









    public function search(Request $request)
    {
        $query = trim($request->input('query'));

        if (!$query) {
            return response()->json([
                'message' => 'Empty search query.',
                'data' => [],
            ], 400);
        }

        $normalizedQuery = trim(preg_replace('/\s+/', ' ', $query));
        $normalizedQuery = rtrim($normalizedQuery, " \t\n\r\0\x0B.,،؛؟");
        Log::info('Normalized initial search query:', ['query' => $normalizedQuery]);
        // Extract all potential search parameters from the normalized query.
        $searchParams = $this->extractSearchParams($normalizedQuery);
        Log::info('Extracted search parameters:', $searchParams);

        // Start building the Eloquent query with necessary relationships.
        $results = RealEstate::with(['location', 'images', 'properties']);

        // --- Apply Filters Based on Extracted Parameters ---

        // Apply Location Filter
        if ($searchParams['location']) {
            $locationQuery = $searchParams['location'];
            // Find location IDs that match the extracted location query
            $locationIds = RealEstate_Location::where('district', 'like', '%' . $locationQuery . '%')
                ->pluck('id');
            if ($locationIds->isNotEmpty()) {
                $results->whereIn('real_estate_location_id', $locationIds);
                Log::info('Applied location filter:', ['location' => $locationQuery, 'ids' => $locationIds->toArray()]);
            } else {
                Log::warning('No matching location found for query:', ['location' => $locationQuery]);
            }
        }

        // Apply Price Filter (min)
        if ($searchParams['price_min'] !== null) { // Use strict comparison as 0 is a valid min price
            $results->where('price', '>=', $searchParams['price_min']);
            Log::info('Applied price min filter:', ['min_price' => $searchParams['price_min']]);
        }
        // Apply Price Filter (max)
        if ($searchParams['price_max'] !== null) { // Use strict comparison as 0 is a valid max price
            $results->where('price', '<=', $searchParams['price_max']);
            Log::info('Applied price max filter:', ['max_price' => $searchParams['price_max']]);
        }

        // Apply Type Filter
        if ($searchParams['type'] && $searchParams['type'] !== 'any') {
            $results->where('type', $searchParams['type']);
            Log::info('Applied type filter:', ['type' => $searchParams['type']]);
        }

        // Apply General Keywords Filter for any remaining unclassified terms
        if (!empty($searchParams['general_keywords'])) {
            $generalQueryString = implode('%', $searchParams['general_keywords']); // Use % for a broader 'like' match
            Log::info('Applying general keywords filter:', ['keywords' => $searchParams['general_keywords'], 'like_string' => $generalQueryString]);

            $results->where(function ($q) use ($generalQueryString) {
                $q->where('description', 'like', "%{$generalQueryString}%")
                    ->orWhereHas('properties', function ($q_prop) use ($generalQueryString) {
                        $q_prop->where('direction', 'like', "%{$generalQueryString}%");
                    });
                // You might consider adding a search in location district here as well,
                // if not already caught by the specific 'location' filter
                // ->orWhereHas('location', function ($q_loc) use ($generalQueryString) {
                //     $q_loc->where('district', 'like', "%{$generalQueryString}%");
                // });
            });
        }

        // Execute the query and return the results.
        return response()->json([
            'data' => $results->get(),
        ]);
    }

    protected function extractSearchParams(string $query): array
    {
        $params = [
            'location' => null,
            'price_min' => null,
            'price_max' => null,
            'type' => null,
            'general_keywords' => []
        ];

        // Keep a copy of the original query (lowercased) to remove extracted parts later for general keywords.
        $workingQuery = mb_strtolower($query);

        $rentKeywords = ['إيجار', 'اجار', 'للايجار'];
        $buyKeywords = ['شراء', 'تمليك', 'للبيع'];

        // Check for rent keywords
        foreach ($rentKeywords as $keyword) {
            if (str_contains($workingQuery, $keyword)) {
                $params['type'] = 'rent';
                // Remove the keyword from the working query to prevent it from being classified as general or location.
                $workingQuery = str_replace($keyword, '', $workingQuery);
                break; // Found a type, no need to check other rent keywords
            }
        }

        // If not rent, check for buy keywords
        if (!$params['type']) {
            foreach ($buyKeywords as $keyword) {
                if (str_contains($workingQuery, $keyword)) {
                    $params['type'] = 'sale';
                    $workingQuery = str_replace($keyword, '', $workingQuery);
                    break; // Found a type, no need to check other buy keywords
                }
            }
        }


        preg_match_all('/(?:بسعر|من|الى|حوالي)?\s*(\d+)(?:\s*الى\s*(\d+))?/u', $workingQuery, $matches, PREG_SET_ORDER);

        $extractedNumbers = [];
        foreach ($matches as $match) {
            if (isset($match[1])) {
                $extractedNumbers[] = (int) $match[1];
            }
            if (isset($match[2])) {
                $extractedNumbers[] = (int) $match[2];
            }
            $workingQuery = preg_replace('/' . preg_quote($match[0], '/') . '/u', '', $workingQuery, 1);
        }

        if (!empty($extractedNumbers)) {
            sort($extractedNumbers); // Sort to easily pick min and max

            $params['price_min'] = $extractedNumbers[0];
            if (count($extractedNumbers) > 1) {
                $params['price_max'] = end($extractedNumbers); // The largest number will be the max
            } else {
                // If only one number is found, it could be a specific price or a minimum.
                // For simplicity, we'll set it as both min and max if no range is clear,
                // or you could have more complex logic (e.g., if preceded by 'over'/'under').
                // Here, we'll assume it's a specific price, so min=max.
                // If only one price is given, often it implies "up to" or "around".
                // Let's refine this: if it's the only number and no "from" keyword, assume it's a max.
                // Or, if it's the only number, it could be both min and max for an exact match.
                // For now, setting it as min, and if no other number, max stays null, letting the query handle it.
                // For example "بيت ب1500" would search >= 1500 and <= 1500.
                // Let's make it min and max for exact match in this case.
                $params['price_max'] = $extractedNumbers[0];
            }
        }

        $locationKeywords = ['مكانه', 'يقع', 'يتوسط', 'داخل', 'بـ', 'في', 'منطقة', 'حي', 'قرب'];

        $tempWorkingQueryParts = explode(' ', $workingQuery);
        $tempWorkingQueryPartsCleaned = [];

        foreach ($tempWorkingQueryParts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            $isLocationKeyword = false;
            foreach ($locationKeywords as $keyword) {
                if (str_contains($part, $keyword)) {
                    $isLocationKeyword = true;
                    break;
                }
            }

            if ($isLocationKeyword) {
            } else {
                $tempWorkingQueryPartsCleaned[] = $part;
            }
        }


        $potentialLocation = implode(' ', $tempWorkingQueryPartsCleaned);
        if (!empty($potentialLocation) && strlen($potentialLocation) > 2) { // Avoid very short, meaningless strings
            $params['location'] = $potentialLocation;
            // Remove the identified location from the working query
            foreach ($locationKeywords as $keyword) {
                $workingQuery = str_replace($keyword, '', $workingQuery);
            }
            $workingQuery = str_replace($potentialLocation, '', $workingQuery);
        }


        // --- 4. Identify General Keywords (What's left) ---
        // Clean up the working query further by removing any remaining location keywords that might not have been
        // explicitly part of the extracted location string but were in the original query.
        foreach ($locationKeywords as $keyword) {
            $workingQuery = str_replace($keyword, '', $workingQuery);
        }

        // Split the remaining cleaned working query into individual terms
        $remainingTerms = array_values(array_filter(explode(' ', trim($workingQuery))));

        // Filter out common stop words or very short, non-descriptive terms if desired
        // Example stop words (extend as needed for Arabic): ['و', 'او', 'على', 'من', 'في', 'احد']
        $stopWords = ['و', 'او', 'على', 'من', 'في', 'احد', 'الي', 'هذا', 'هذه', 'هو', 'هي', 'ال', 'ب']; // Add more Arabic stop words
        $filteredGeneralKeywords = [];
        foreach ($remainingTerms as $term) {
            $term = trim($term);
            if (!empty($term) && strlen($term) > 1 && !in_array($term, $stopWords)) { // Exclude very short terms and stop words
                $filteredGeneralKeywords[] = $term;
            }
        }
        $params['general_keywords'] = $filteredGeneralKeywords;

        return $params;
    }
}

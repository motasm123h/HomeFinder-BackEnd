<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SearchService;
use App\Models\RealEstate;
use App\Models\RealEstate_Location;
use App\Services\PropertyMatcher;

class SearchController extends Controller
{
    public function __construct(private SearchService $searchService){

    }
    public function mostSearched()
    {
        $results = $this->searchService->getMostSearchedRealEstates();
        return response()->json($results);
    }


    public function mostWatch(){
        $results = $this->searchService->getMostWatchedRealEstates();
        return response()->json($results);
    }
   
    public function preferences(Request $request){
        $validated = $request->validate([
            'room_no' => 'sometimes|integer',
            'price' => 'sometimes|in:1,2,3',
            'space_status' => 'sometimes|integer',
        ]);

        $matcher = new PropertyMatcher();
        $results = $matcher->findMatches($validated);
        return response()->json([
            'matches' => $results,
            'searchInput' => $validated
        ]);
        
    }
    
    public function byLocation(Request $request){

    }

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


    public function search(Request $request)
    {
        $query = trim($request->input('query'));

        if (!$query) {
            return response()->json([
                'message' => 'Empty search query.',
                'data' => [],
            ], 400);
        }

        $query = trim(preg_replace('/\s+/', ' ', $query));
        $query = rtrim($query, " \t\n\r\0\x0B.,،؛؟");
        \Log::info('Normalized search query:', ['query' => $query]);

        $searchType = $this->detectSearchIntent($query);
        \Log::info('Detected search intent:', ['type' => $searchType]);

        $results = RealEstate::with(['location', 'images', 'properties']);

        switch ($searchType) {
            case 'location':
                $locationQuery = $this->extractLocationQuery($query);
                $locationIds = RealEstate_Location::where('district', 'like', '%' . $locationQuery . '%')
                    ->pluck('id');
                $results->whereIn('real_estate_location_id', $locationIds);
                break;

            case 'price':
                $priceRange = $this->extractPriceRange($query);
                if ($priceRange['min']) {
                    $results->where('price', '>=', $priceRange['min']);
                }
                if ($priceRange['max']) {
                    $results->where('price', '<=', $priceRange['max']);
                }
                break;

            case 'type':
                $type = $this->extractType($query);
                $results->where('type', $type);
                break;

            default: 
                $locationIds = RealEstate_Location::where('district', 'like', '%' . $query . '%')
                    ->pluck('id');
                $results->where(function ($q) use ($query, $locationIds) {
                    $q->where('description', 'like', "%$query%")
                    ->orWhereIn('real_estate_location_id', $locationIds)
                    ->orWhereHas('properties', function ($q) use ($query) {
                        $q->where('direction', 'like', "%$query%");
                    });
                });
        }

        return response()->json([
            'data' => $results->get(),
        ]);
    }

    protected function detectSearchIntent(string $query): string
    {
        $locationKeywords = ['مكانه', 'يقع', 'يتوسط', 'داخل', 'بـ', 'في', 'منطقة', 'حي', 'قرب'];
        $priceKeywords = ['سعره', 'تكلفته', 'السعر', 'الثمن', 'بسعر', 'بقيمة', 'بحوالي'];
        $typeKeywords = ['إيجار', 'اجار', 'شراء', 'تمليك', 'للايجار', 'للبيع'];

        $query = mb_strtolower($query);

        foreach ($locationKeywords as $keyword) {
            if (str_contains($query, $keyword)) {
                return 'location';
            }
        }

        foreach ($priceKeywords as $keyword) {
            if (str_contains($query, $keyword)) {
                return 'price';
            }
        }

        foreach ($typeKeywords as $keyword) {
            if (str_contains($query, $keyword)) {
                return 'type';
            }
        }

        return 'general';
    }

    protected function extractLocationQuery(string $query): string
    {
        $keywords = ['مكانه', 'يقع', 'يتوسط', 'داخل', 'بـ', 'في', 'منطقة', 'حي', 'قرب'];
        $query = str_replace($keywords, '', $query);
        return trim($query);
    }

    protected function extractPriceRange(string $query): array
    {
        preg_match_all('/\d+/', $query, $matches);
        $numbers = $matches[0] ?? [];

        return [
            'min' => $numbers[0] ?? null,
            'max' => $numbers[1] ?? null,
        ];
    }

    protected function extractType(string $query): string
    {
        $rentKeywords = ['إيجار', 'اجار', 'للايجار'];
        $buyKeywords = ['شراء', 'تمليك', 'للبيع'];

        $query = mb_strtolower($query);

        foreach ($rentKeywords as $keyword) {
            if (str_contains($query, $keyword)) {
                return 'rent';
            }
        }

        foreach ($buyKeywords as $keyword) {
            if (str_contains($query, $keyword)) {
                return 'buy';
            }
        }

        return 'any'; 
    }



}

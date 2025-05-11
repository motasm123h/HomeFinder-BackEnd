<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SearchService;
use App\Models\RealEstate;
use App\Models\RealEstate_Location;

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

    }
    
    public function byLocation(Request $request){

    }

public function search(Request $request)
{
    // Get the query and trim it
    $query = trim($request->input('query'));

    if (!$query) {
        return response()->json([
            'message' => 'Empty search query.',
            'data' => [],
        ], 400);
    }

    // Normalize spaces
    $query = trim(preg_replace('/\s+/', ' ', $query));

    // Remove trailing punctuation (dot, comma, etc.)
    $query = rtrim($query, " \t\n\r\0\x0B.,،؛؟");

    \Log::info('Normalized search query:', ['query' => $query]);

    // Get matching location IDs
    $locationIds = RealEstate_Location::where('district', 'like', '%' . $query . '%')->pluck('id');

    // Search RealEstates
    $results = RealEstate::with(['location', 'images', 'properties'])
        ->where(function ($q) use ($query, $locationIds) {
            $q->where('description', 'like', "%$query%")
              ->orWhereIn('real_estate_location_id', $locationIds)
              ->orWhereHas('properties', function ($q) use ($query) {
                  $q->where('direction', 'like', "%$query%");
              });
        })
        ->get();

    return response()->json([
        'data' => $results,
    ]);
}

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SearchService;

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
}

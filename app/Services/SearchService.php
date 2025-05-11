<?php
namespace App\Services;

use App\Models\{RealEstate, Search_Log};
use Illuminate\Support\Facades\DB;

class SearchService
{
    
    public function getMostSearchedRealEstates(int $limit = 5)
{
    $topSearches = Search_Log::select(
        'key',
        'value',
        DB::raw('COUNT(*) as search_weight'),
        DB::raw('CASE
            WHEN `key` = "price_range" THEN "range"
            WHEN `key` IN ("price", "min_price", "max_price") AND `value` REGEXP "^[0-9]+$" THEN "numeric"
            ELSE "string"
        END as value_type')
    )
    ->groupBy('key', 'value')
    ->orderByDesc('search_weight')
    ->limit($limit)
    ->get();

    return RealEstate::where(function ($query) use ($topSearches) {
        foreach ($topSearches as $search) {
            $query->orWhere(function ($subQuery) use ($search) {
                $key = $search->key;
                $value = $search->value;

                switch ($search->value_type) {
                    case 'range':
                        if (preg_match('/^(\d+)-(\d+)$/', $value, $matches)) {
                            $subQuery->whereBetween('price', [(float)$matches[1], (float)$matches[2]]);
                        }
                        break;

                    case 'numeric':
                        // Assume numeric search is on main RealEstate
                        $subQuery->where($key, (float)$value);
                        break;

                    default:
                        // Handle strings
                        switch ($key) {
                            case 'kind':
                            case 'type':
                                $subQuery->where($key, 'like', "%{$value}%");
                                break;

                            case 'direction':
                            case 'room_no':
                            case 'floor':
                            case 'ownership_type':
                                // In RealEstate_properties
                                $subQuery->whereHas('properties', function ($q) use ($key, $value) {
                                    $q->where($key, 'like', "%{$value}%");
                                });
                                break;

                            case 'location':
                            case 'district':
                                // In RealEstate_Location
                                $subQuery->whereHas('location', function ($q) use ($value) {
                                    $q->where('district', 'like', "%{$value}%")
                                      ->orWhere('city', 'like', "%{$value}%");
                                });
                                break;

                            default:
                                // fallback to description
                                $subQuery->where('description', 'like', "%{$value}%");
                                break;
                        }
                        break;
                }
            });
        }
    })
    ->with(['location', 'images', 'properties'])
    ->get();
}


    public function getMostWatchedRealEstates(int $limit = 10)
    {
        return RealEstate::whereHas('view')
        ->with(['location', 'images'])
        ->withCount(['view as counter_sum' => function($query) {
            $query->select(DB::raw('SUM(counter)'));
        }])
        ->orderByDesc('counter_sum')
        ->limit($limit)
        ->paginate(10);
    }

    public function logSearch(string $key, int $value = 1, User $user = null)
    {
        return Search_Log::create([
            'key' => $key,
            'value' => $value,
            'user_id' => $user?->id
        ]);
    }
}
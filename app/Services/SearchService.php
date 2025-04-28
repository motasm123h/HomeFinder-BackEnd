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

            return $topSearches;


    
        return RealEstate::where(function($query) use ($topSearches) {
                foreach ($topSearches as $search) {
                    switch ($search->value_type) {
                        case 'range':
                            if (preg_match('/^(\d+)-(\d+)$/', $search->value, $matches)) {
                                $query->orWhereBetween('price', [(float)$matches[1], (float)$matches[2]]);
                            }
                            break;
                            
                        case 'numeric':
                            $query->orWhere($search->key, (float)$search->value);
                            break;
                            
                        default: 
                            $query->orWhere(function($q) use ($search) {
                                if ($search->key === 'kind') {
                                    $q->where('kind', 'like', "%{$search->value}%");
                                } elseif ($search->key === 'type') {
                                    $q->where('type', 'like', "%{$search->value}%");
                                } elseif ($search->key === 'location') {
                                    $q->whereHas('location', function($locQuery) use ($search) {
                                        $locQuery->where('city', 'like', "%{$search->value}%")
                                                ->orWhere('district', 'like', "%{$search->value}%");
                                    });
                                } else {
                                    $q->where('description', 'like', "%{$search->value}%");
                                }
                            });
                    }
                }
            })
            ->with(['location', 'images'])
            ->paginate(10);
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
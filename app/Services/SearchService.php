<?php

namespace App\Services;

use App\Models\{RealEstate, Search_Log};
use Illuminate\Support\Facades\DB;

class SearchService
{

    public function getMostSearchedRealEstates(int $limit = 5)
    {
        // Get the current database connection name
        $driver = DB::connection()->getDriverName();

        $topSearches = Search_Log::select('key', 'value', DB::raw('COUNT(*) as search_weight'))
            ->groupBy('key', 'value')
            ->orderByDesc('search_weight')
            ->limit($limit);

        // Add the database-specific CASE statement
        if ($driver === 'sqlite') {
            $topSearches->selectRaw('CASE
                WHEN `key` = "price_range" THEN "range"
                WHEN `key` IN ("price", "min_price", "max_price") AND CAST(value AS UNSIGNED) = value THEN "numeric"
                ELSE "string"
            END as value_type');
        } else {
            // This includes MySQL and other drivers that support REGEXP
            $topSearches->selectRaw('CASE
                WHEN `key` = "price_range" THEN "range"
                WHEN `key` IN ("price", "min_price", "max_price") AND `value` REGEXP "^[0-9]+$" THEN "numeric"
                ELSE "string"
            END as value_type');
        }

        $topSearches = $topSearches->get();

        return $topSearches;

        // The rest of your code remains the same
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
                            $subQuery->where($key, (float)$value);
                            break;

                        default:
                            switch ($key) {
                                case 'kind':
                                case 'type':
                                    $subQuery->where($key, 'like', "%{$value}%");
                                    break;

                                case 'direction':
                                case 'room_no':
                                case 'floor':
                                case 'ownership_type':
                                    $subQuery->whereHas('properties', function ($q) use ($key, $value) {
                                        $q->where($key, 'like', "%{$value}%");
                                    });
                                    break;

                                case 'location':
                                case 'district':
                                    $subQuery->whereHas('location', function ($q) use ($value) {
                                        $q->where('district', 'like', "%{$value}%")
                                            ->orWhere('city', 'like', "%{$value}%");
                                    });
                                    break;

                                default:
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
            ->withCount(['view as counter_sum' => function ($query) {
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

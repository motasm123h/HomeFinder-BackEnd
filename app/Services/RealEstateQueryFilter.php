<?php

namespace App\Services;

use App\Models\Search_Log;
use Illuminate\Database\Eloquent\Builder;

class RealEstateQueryFilter
{
    private SearchService $service;

    public function apply(Builder $query, array $filters): Builder
    {
        $this->logSearchFilters($filters);

        return $query
            ->when($filters['type'] ?? null, function ($q, $type) {
                return $q->where('type', $type);
            })
            ->when($filters['kind'] ?? null, function ($q, $kind) {
                return $q->where('kind', $kind);
            })
            ->when($filters['max_price'] ?? null, function ($q, $max) {
                return $q->where('price', '<=', $max);
            })
            ->when($filters['location'] ?? null, function ($q) use ($filters) {
                return $this->filterByLocation($q, $filters['location']);
            });
    }

    protected function logSearchFilters(array $filters): void
    {
        foreach ($this->getLoggableFilters($filters) as $key => $value) {
            Search_Log::create([
                'key' => $key,
                'value' => $value,
            ]);
        }
    }

    protected function getLoggableFilters(array $filters): array
    {
        return array_filter([
            'type' => $filters['type'] ?? null,
            'kind' => $filters['kind'] ?? null,
            'location' => $filters['location'] ?? null,
            'price_range' => isset($filters['min_price'], $filters['max_price'])
                ? "{$filters['min_price']}-{$filters['max_price']}"
                : null,
        ]);
    }

    protected function filterByLocation(Builder $query, string $location): Builder
    {
        return $query->whereHas('location', fn ($q) => $q->where('city', 'like', "%{$location}%")
            ->orWhere('district', 'like', "%{$location}%")
        );
    }

    protected function sortResults(Builder $query, string $sortField): Builder
    {
        $direction = str_starts_with($sortField, '-') ? 'desc' : 'asc';
        $field = ltrim($sortField, '-');
        $allowedSorts = ['price', 'created_at', 'area'];

        return in_array($field, $allowedSorts)
            ? $query->orderBy($field, $direction)
            : $query;
    }
}

<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Common Query Scopes Trait
 * 
 * Provides reusable query scopes for filtering and searching.
 * Can be used by models that need date filtering and title search functionality.
 */
trait CommonQueryScopes
{
    /**
     * Filter by date range
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|Carbon|null $startDate Start date (Y-m-d format or Carbon instance)
     * @param string|Carbon|null $endDate End date (Y-m-d format or Carbon instance)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByDate(Builder $query, $startDate = null, $endDate = null): Builder
    {
        if ($startDate) {
            $startDate = is_string($startDate) ? Carbon::parse($startDate)->startOfDay() : $startDate->startOfDay();
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $endDate = is_string($endDate) ? Carbon::parse($endDate)->endOfDay() : $endDate->endOfDay();
            $query->where('date', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Search by title (case-insensitive partial match)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $searchTerm Search term to match against title
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByTitle(Builder $query, ?string $searchTerm): Builder
    {
        if ($searchTerm && trim($searchTerm) !== '') {
            $query->where('title', 'LIKE', '%' . trim($searchTerm) . '%');
        }

        return $query;
    }
}


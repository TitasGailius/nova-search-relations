<?php

namespace Titasgailius\SearchRelations;

use Closure;
use Illuminate\Database\Eloquent\Builder;

trait SearchesRelations
{
    /**
     * Determine if this resource is searchable.
     *
     * @return bool
     */
    public static function searchable()
    {
        return parent::searchable()
            || ! empty(static::$searchRelations);
    }

    /**
     * Get the searchable columns for the resource.
     *
     * @return array
     */
    public static function searchableRelations(): array
    {
        if (static::isGlobalSearch()) {
            return static::globallySearchableRelations();
        }

        return static::$searchRelations ?? [];
    }

    /**
     * Get the globally searchable relations for the resource.
     *
     * @return array
     */
    public static function globallySearchableRelations(): array
    {
        if (isset(static::$globalSearchRelations)) {
            return static::$globalSearchRelations;
        }

        // This is a deprecated property. To turn off the global search, instead of setting
        // the "$searchRelationsGlobally" property to false, it is recommended to set the
        // "$globalSearchRelations" property to an empty array.
        if (! (static::$searchRelationsGlobally ?? true)) {
            return [];
        }

        return static::$searchRelations ?? [];
    }

    /**
     * Determine whether current request is for global search.
     *
     * @return bool
     */
    protected static function isGlobalSearch()
    {
        return request()->route()->action['uses'] === 'Laravel\Nova\Http\Controllers\SearchController@index';
    }

    /**
     * Apply the search query to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applySearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            parent::applySearch($query, $search);
            static::applyRelationSearch($query, $search);
        });
    }

    /**
     * Apply the relationship search query to the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applyRelationSearch(Builder $query, string $search): Builder
    {
        return (new RelationSearchQuery(
            static::searchableRelations()
        ))->apply($query, $search);
    }
}

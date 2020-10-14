<?php

namespace Titasgailius\SearchRelations;

use Illuminate\Database\Eloquent\Builder;
use Titasgailius\SearchRelations\Contracts\Search;
use Titasgailius\SearchRelations\Searches\RelationSearch;

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

        if (static::globalSearchDisabledForRelations()) {
            return [];
        }

        return static::$searchRelations ?? [];
    }

    /**
     * Determine if a global search is disabled for the relationships.
     *
     * @return boolean
     */
    protected static function globalSearchDisabledForRelations(): bool
    {
        return isset(static::$searchRelationsGlobally)
            && ! static::$searchRelationsGlobally;
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
        foreach (static::searchableRelations() as $relation => $columns) {
            static::parseSearch($columns, $relation)->apply($query, $search);
        }

        return $query;
    }

    /**
     * Parse search.
     *
     * @param  mixed $search
     * @param  string $relation
     * @return \Titasgailius\SearchRelations\Contracts\Search
     */
    protected static function parseSearch($search, $relation): Search
    {
        if ($search instanceof Search) {
            return $search;
        }

        if (is_array($search)) {
            return new RelationSearch($search);
        }

        throw new InvalidArgumentException(sprintf(
            'Unsupported search configuration in [%s] resource for [%s] relationship.',
            static::class, $relation
        ));
    }
}

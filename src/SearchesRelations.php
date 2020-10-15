<?php

namespace Titasgailius\SearchRelations;

use InvalidArgumentException;
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
            || ! empty(static::resolveSearchableRelations());
    }

    /**
     * Get the searchable columns for the resource.
     *
     * @return array
     */
    public static function searchableRelations(): array
    {
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

        if (static::$searchRelationsGlobally ?? true) {
            return static::searchableRelations();
        }

        return [];
    }

    /**
     * Resolve searchable relations for the current request.
     *
     * @return array
     */
    protected static function resolveSearchableRelations(): array
    {
        return static::isGlobalSearch()
            ? static::globallySearchableRelations()
            : static::searchableRelations();
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
        foreach (static::resolveSearchableRelations() as $relation => $columns) {
            static::parseSearch($relation, $columns)->apply($query, $relation, $search);
        }

        return $query;
    }

    /**
     * Parse search.
     *
     * @param  string $relation
     * @param  mixed $columns
     * @return \Titasgailius\SearchRelations\Contracts\Search
     */
    protected static function parseSearch($relation, $columns): Search
    {
        if ($columns instanceof Search) {
            return $columns;
        }

        if (is_array($columns)) {
            return new RelationSearch($columns);
        }

        throw new InvalidArgumentException(sprintf(
            'Unsupported search configuration in [%s] resource for [%s] relationship.',
            static::class, $relation
        ));
    }
}

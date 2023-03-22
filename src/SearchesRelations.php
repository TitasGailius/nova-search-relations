<?php

namespace Titasgailius\SearchRelations;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Titasgailius\SearchRelations\Contracts\Search;
use Titasgailius\SearchRelations\Searches\RelationSearch;

trait SearchesRelations
{
    /**
     * Give the oppurtunity to change the search split character.
     * Set to a character to enable splitting search 
     * @var string
     */
    protected static $searchSplitCharacter = null;
    
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applySearch($query, $search)
    {
        /**
         * If $searchSplitCharacter is null or...
         * If search does not contain $searchSplitCharacter...
         * we can use the default search
         */
        if (self::$searchSplitCharacter === null || strpos($search, self::$searchSplitCharacter) === false) {
            return $query->where(function ($query) use ($search) {
                parent::applySearch($query, $search);
                static::applyRelationSearch($query, $search);
            });
        }

        $query->where(function (Builder $query) use ($search) {
            foreach (explode(self::$searchSplitCharacter, $search) as $search) {
                $query->orWhere(function ($query) use ($search) {
                    parent::applySearch($query, $search);
                    static::applyRelationSearch($query, $search);
                });
            }
        });
        
        return $query;
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

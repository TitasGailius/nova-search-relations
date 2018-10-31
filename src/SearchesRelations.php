<?php

namespace Masardee\SearchRelations;

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
        return parent::searchable() || ! empty(static::$searchRelations);
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
            static::applyRelationSearch($query, $search, static::searchableRelations());
        });
    }

    /**
     * Apply the relationship search query to the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @param  array   $relations
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applyRelationSearch(Builder $query, string $search, array $relations): Builder
    {
        foreach ($relations as $relation => $columns) {
            $query->orWhereHas($relation, function ($query) use ($columns, $search) {
                $stringColumns = [];
                $relationColumns = [];
                foreach($columns as $nestedRelation => $column){
                    if(is_array($column))
                        $relationColumns[$nestedRelation] = $column;
                    else
                        $stringColumns[] = $column;
                }
                $query->where(static::searchQueryApplier($stringColumns, $search));
                static::applyRelationSearch($query, $search, $relationColumns);
            });
        }

        return $query;
    }

    /**
     * Returns a Closure that applies a search query for a given columns.
     *
     * @param  array $columns
     * @param  string $search
     * @return \Closure
     */
    protected static function searchQueryApplier(array $columns, string $search): Closure
    {
        return function ($query) use ($columns, $search) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'LIKE', '%'.$search.'%');
            }
        };
    }
}

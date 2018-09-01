<?php

namespace Titasgailius\SearchRelations;

use Closure;
use Illuminate\Database\Eloquent\Builder;

trait SearchesRelations
{
    /**
     * Apply the search query to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applySearch($query, $search)
    {
        return tap(parent::applySearch($query, $search), function ($query) use ($search) {
            static::applyRelationSearch($query, $search);
        });
    }

    /**
     * Apply the relationship search query to the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return void
     */
    public static function applyRelationSearch(Builder $query, string $search)
    {
        foreach (static::searchableRelations() as $relation => $columns) {
            $query->orWhereHas($relation, function ($query) use ($columns, $search) {
                $query->where(static::searchQueryApplier($columns, $search));
            });
        }

        // All search conditionals have to be combined into 1 nested conditional.
        // We just applied a query to search for relationship columns while Nova
        // has also applied a separate query to search for model columns. That's
        // why we merge last 2 conditionals into 1 nested conditional which leaves
        // us with this nicely formatted query that also seatches in relationships.
        // SELECT ? FROM ? WHERE ($searchQuery OR $relationQuery)
        static::mergeLastWheres($query, 2);
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
     * Returns a Closure that applies a search query for a given columns.
     *
     * @param  array $columns
     * @param  string $search
     * @return \Closure
     */
    public static function searchQueryApplier(array $columns, string $search): Closure
    {
        return function ($query) use ($columns, $search) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'LIKE', '%'.$search.'%');
            }
        };
    }

    /**
     * Merge last where conditionals into a 1 nested conditional.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param  int $count
     * @return void
     */
    public static function mergeLastWheres(Builder $query, int $count)
    {
        $query = $query->getQuery();
        $queries = array_splice($query->wheres, $count);

        $query->where(function ($query) use ($queries) {
            $query->wheres = array_merge($query->wheres, $queries);
        });
    }
}

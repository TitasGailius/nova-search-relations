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
        return parent::searchable() || !empty(static::$searchRelations);
    }

    /**
     * Get the searchable columns for the resource.
     *
     * @return array
     */
    public static function searchableRelations(): array
    {
        $searchRelationsGlobally = static::$searchRelationsGlobally ?? true;

        if (!$searchRelationsGlobally && static::isGlobalSearch()) {
            return static::$globalSearchRelations ?? [];
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
        foreach (static::searchableRelations() as $relation => $columns) {
            $query->orWhereHas($relation, function ($query) use ($columns, $search) {
                $query->where(static::searchQueryApplier($columns, $search));
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
            $model = $query->getModel();
            $operator = static::operator($query);

            foreach ($columns as $column) {
                $query->orWhere($model->qualifyColumn($column), $operator, '%'.$search.'%');
            }
        };
    }

    /**
     * Resolve the query operator.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return string
     */
    protected static function operator(Builder $query): string
    {
        if ($query->getModel()->getConnection()->getDriverName() === 'pgsql') {
            return 'ILIKE';
        }

        return 'LIKE';
    }
}

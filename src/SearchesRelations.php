<?php

namespace Titasgailius\SearchRelations;

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
        foreach (static::searchableRelations() as $relation => $columns) {
            $query->orWhereHas($relation, function ($query) use ($columns, $search) {
                static::applyColumnSearch($query, $columns, $search);
            });
        }

        return $query;
    }

    /**
     * Apply search for the given columns.
     *
     * @param  Builder $query
     * @param  array $columns
     * @param  string $search
     * @return Builder
     */
    protected static function applyColumnSearch(Builder $query, array $columns, string $search): Builder
    {
        return $query->where(function ($query) use ($columns, $search) {
            $model = $query->getModel();
            $operator = static::operator($query);

            foreach ($columns as $column) {
                $query->orWhere($model->qualifyColumn($column), $operator, '%'.$search.'%');
            }

            return $query;
        });
    }

    /**
     * Get the like operator for the given query.
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

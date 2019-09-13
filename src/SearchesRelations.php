<?php

namespace Titasgailius\SearchRelations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $search
     *
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $search
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applyRelationSearch(Builder $query, string $search): Builder
    {
        $parts = explode(',', $search);
        foreach ($parts as $part) {
            $query->where(function(Builder $query) use($part) {
                foreach (static::searchableRelations() as $relation => $columns) {

                    $relatedQuery = str_replace(
                        '?',
                        "'$part'",
                        static::searchQueryApplier($query->getModel()->{$relation}()->getRelated(),
                            $columns,
                            $part
                        )->toSql() . " AND " . $query->getModel()->{$relation}()->getQualifiedForeignKeyName() . ' = ' . $query->getModel()->{$relation}()->getQualifiedOwnerKeyName()
                    );


                    $query->orWhereRaw("(exists($relatedQuery))");
                }
            });


        }
        
        return $query;
    }

    /**
     * Returns a Closure that applies a search query for a given columns.
     *
     * @param array  $columns
     * @param string $search
     *
     * @return \Closure
     */
    protected static function searchQueryApplier(Model $model, array $columns, string $search): Builder
    {
        $query = $model->newQuery();
        foreach ($columns as $column) {
            $parts = explode(',', $search);
            foreach ($parts as $part) {
                $query->orWhere($model->qualifyColumn($column), 'LIKE', '%'.$part.'%');
            }
        }

        return $query;
    }
}

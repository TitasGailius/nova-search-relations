<?php

namespace Titasgailius\SearchRelations;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

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
            if (static::isRelationMorphable($relation)) {
                $query->orWhereHasMorph(
                    $relation,
                    static::getMorphableClasses($relation)->toArray(),
                    function ($query) use ($columns, $search) {
                        $query->where(static::searchQueryApplier($columns, $search));
                    });
            } else {
                $query->orWhereHas($relation, function ($query) use ($columns, $search) {
                    static::searchQueryApplier($columns, $search);
                    $query->where(static::searchQueryApplier($columns, $search));
                });
            }
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

    /**
     * Get Relation Class of Ressource Model
     *
     * @param string $relationName
     * @return mixed
     */
    public static function getRelation(string $relationName) {
        return (new static::$model())->$relationName();
    }

    /**
     * Checks if Relation is MorphTo
     *
     * @param string $relationName
     * @return bool
     */
    public static function isRelationMorphable(string $relationName) {
        $relation = static::getRelation($relationName);
        return get_class($relation) === MorphTo::class ||
            is_subclass_of(
                (new static::$model())->$relationName(),
                MorphTo::class
            );
    }

    /**
     * Returns used Relationships of Morphable Fields
     *
     * @param string $relationName
     * @return mixed
     */
    public static function getMorphableClasses(string $relationName) : Collection
    {
        $relation = static::getRelation($relationName);
        return \DB::table($relation->getParent()->getTable())
            ->select($relation->getMorphType())
            ->distinct()
            ->pluck($relation->getMorphType())
            ->filter(function($class) use ($relation) {
                return ($class !== get_class($relation->getParent()));
            });
    }
}

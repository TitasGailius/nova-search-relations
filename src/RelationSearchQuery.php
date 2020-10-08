<?php

namespace Titasgailius\SearchRelations;

use Illuminate\Database\Eloquent\Builder;

class RelationSearchQuery
{
    /**
     * Searchable relations.
     *
     * @var array
     */
    protected $relations;

    /**
     * Instantiate a new search query instance.
     *
     * @param array $relations
     */
    public function __construct(array $relations)
    {
        $this->relations = $relations;
    }

    /**
     * Apply search query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Builder $query, $search): Builder
    {
        foreach ($this->relations as $relation => $columns) {
            $query->orWhereHas($relation, function ($query) use ($columns, $search) {
                (new SearchQuery($columns))->apply($query, $search);
            });
        }

        return $query;
    }
}

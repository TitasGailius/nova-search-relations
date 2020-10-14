<?php

namespace Titasgailius\SearchRelations\Searches;

use Illuminate\Database\Eloquent\Builder;
use Titasgailius\SearchRelations\Contracts\Search;

class RelationSearch implements Search
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
     * Apply search.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Builder $builder, string $search): Builder
    {
        foreach ($this->relations as $relation => $columns) {
            $query->orWhereHas($relation, function ($query) use ($columns, $search) {
                (new SearchQuery($columns))->apply($query, $search);
            });
        }

        return $query;
    }
}

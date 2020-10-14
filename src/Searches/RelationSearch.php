<?php

namespace Titasgailius\SearchRelations\Searches;

use Illuminate\Database\Eloquent\Builder;
use Titasgailius\SearchRelations\Contracts\Search;
use Illuminate\Database\Eloquent\RelationNotFoundException;

class RelationSearch implements Search
{
    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $columns;

    /**
     * Instantiate a new search query instance.
     *
     * @param array $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Apply search for the given relation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $relation
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Builder $query, string $relation, string $search): Builder
    {
        $this->ensureRelationshipExists($query, $relation);

        $query->orWhereHas($relation, function ($query) use ($relation, $search) {
            return $this->columnSearch()->apply($query, $relation, $search);
        });

        return $query;
    }

    /**
     * Ensure that the specified relationship exists.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $relation
     * @return void
     */
    protected function ensureRelationshipExists(Builder $query, string $relation)
    {
        $query->getRelation($relation);
    }

    /**
     * Apply column search.
     *
     * @return \Titasgailius\SearchRelations\Contracts\Search
     */
    protected function columnSearch(): Search
    {
        return new ColumnSearch($this->columns);
    }
}

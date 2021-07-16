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
        $query->orWhereHas($relation, function ($query) use ($relation, $search) {
            return $this->columnSearch()->apply($query, $relation, $search);
        });

        return $query;
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

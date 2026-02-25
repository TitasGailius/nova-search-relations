<?php

namespace Titasgailius\SearchRelations\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

interface Search
{
    /**
     * Apply search for the given relation.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Builder  $query
     * @param  string  $relation
     * @param  string  $search
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     */
    public function apply(Builder $query, string $relation, string $search): Builder;
}

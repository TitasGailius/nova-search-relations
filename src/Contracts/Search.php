<?php

namespace Titasgailius\SearchRelations\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Search
{
    /**
     * Apply search.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Builder $builder, string $search): Builder;
}

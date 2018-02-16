<?php

namespace Marcelgwerder\ApiHandler\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Filter
{
    public function apply(Builder $builder, string $value, string $property = null, string $relation = null);
}

<?php

namespace Marcelgwerder\ApiHandler\Filters;

use Illuminate\Database\Eloquent\Builder;
use Marcelgwerder\ApiHandler\Contracts\Filter;

class EqualFilter implements Filter
{
    public function apply(Builder $builder, string $value, string $property = null, string $relation = null)
    {
        $builder->where($property, $value);
    }
}

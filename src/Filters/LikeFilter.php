<?php

namespace Marcelgwerder\ApiHandler\Filters;

use Illuminate\Database\Eloquent\Builder;
use Marcelgwerder\ApiHandler\Contracts\Filter;

class LikeFilter implements Filter
{
    public function apply(Builder $builder, string $value, string $property = null, string $relation = null)
    {
        $value = str_replace('*', '%', $value);

        $builder->where($property, 'LIKE', $value);
    }
}

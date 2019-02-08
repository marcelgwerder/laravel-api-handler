<?php

namespace Marcelgwerder\ApiHandler\Filters;

use Illuminate\Database\Eloquent\Builder;
use Marcelgwerder\ApiHandler\Contracts\Filter;

class NotInFilter implements Filter
{
    public function apply(Builder $builder, string $value, string $property = null, string $relation = null)
    {
        $values = explode(',', $value);

        $builder->whereNotIn($property, $values);
    }
}

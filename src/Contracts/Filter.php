<?php

namespace Marcelgwerder\ApiHandler\Contracts;

interface Filter
{
    public function apply(Builder $query, string $value, string $property = null, string $relation = null);
}

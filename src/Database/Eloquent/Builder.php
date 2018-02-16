<?php

namespace Marcelgwerder\ApiHandler\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use \ReflectionClass;
use \ReflectionProperty;

class Builder extends EloquentBuilder
{
    public function __construct(EloquentBuilder $builder)
    {
        parent::__construct(clone $builder->getQuery());

        $reflect = new ReflectionClass($builder);
        $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $property->setAccessible(true);
            $this->{$property->name} = $property->getValue($builder);
            $property->setAccessible(false);
        }
    }

    public function callScope(callable $scope, $parameters = [])
    {
        parent::callScope($scope, $parameters);
    }

}

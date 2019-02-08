<?php

namespace Marcelgwerder\ApiHandler\Database\Eloquent;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionException;
use Illuminate\Database\Eloquent\Relations\Relation;
use function Marcelgwerder\ApiHandler\helpers\nullify_empty;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

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

    /**
     * Walk through all the models of the relations in the path.
     *
     * @param  string  $path
     * @param  callable  $callback
     * @return void
     */
    public function walkRelations(string $path, callable $callback = null)
    {
        $relation = [];

        $currentModel = $this->getModel();
        $walkedRealtions = [];

        foreach (explode('.', $path) as $relationName) {
            $parentPath = nullify_empty(implode('.', $walkedRealtions));
            $walkedRealtions[] = $relationName;

            try {
                $method = new ReflectionMethod(get_class($currentModel), $relationName);
                $returnType = $method->getReturnType();
            } catch (ReflectionException $e) {
                $returnType = null;
            }

            if ((string) $returnType !== Relation::class) {
                return false;
            }

            $relation = call_user_func([$currentModel, $relationName]);

            if ($callback) {
                $callback($relation, implode('.', $walkedRealtions), $parentPath);
            }

            $currentModel = $relation->getRelated();
        }

        return true;
    }
}

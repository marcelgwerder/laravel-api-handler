<?php

namespace Marcelgwerder\ApiHandler\Concerns\CleansExpansions;

use Illuminate\Database\Eloquent\Model;

trait CleansExpansions
{
    /**
     * Cleanup the expansions on a single model
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    protected function cleanExpansions(Model $model)
    {
        // Get the relations which already exists on the model (e.g. added by with())
        $requestedExpansions = $this->getRelationsRecursively($model), true);
        // parse the model to an array and get the relations which got added unintentionally
        // (e.g. when accessing a relation in an accessor method or somewhere else)
        $response = $model->toArray();
        $fetchedExpansions = array_fill_keys($this->getRelationsRecursively($model), true);
        
        // remove the unintentionally added relations from the response
        return $this->removeUnrequestedExpansionsFromResponse($response, $requestedExpansions, $fetchedExpansions);
    }

    /**
     * Get all currently loaded relations on a model recursively
     *
     * @param $model
     * @param null $prefix
     * @return array
     */
    protected function getRelationsRecursively($model, $prefix = null)
    {
        $fetchedExpansions = $model->getRelations();
        $relations = [];
        foreach ($fetchedExpansions as $key => $relation) {
            $relations[] = ($prefix ?: '') . $key;
            $relationModel = $model->{$key};
            // If the relation is a collection, just use the first element as all elements of a relation collection are from the same model
            if ($relationModel instanceof Collection) {
                if (count($relationModel) > 0) {
                    $relationModel = $relationModel[0];
                } else {
                    continue;
                }
            }
            // Get the relations of the child model
            if ($relationModel instanceof Model) {
                $relations = array_merge($relations, $this->getRelationsRecursively($relationModel, ($prefix ?: '') . $key . '.'));
            }
        }
        return collect($relations);
    }
    /**
     * Remove all relations which are in the $fetchedExpansions but not in $requestedExpansions from the model array
     *
     * @param $response
     * @param $requestedExpansions
     * @param $fetchedExpansions
     * @param null $prefix
     * @return mixed
     */
    protected function removeUnrequestedExpansions(array $response, $requestedExpansions, $fetchedExpansions, $prefix = null)
    {
        foreach ($response as $key => $attr) {
            $relationKey = ($prefix ?: '') . $key;
            // handle associative arrays as they
            if (isset($fetchedExpansions[$relationKey])) {
                if (!isset($requestedExpansions[$relationKey])) {
                    unset($response[$key]);
                } else if (is_array($attr)) {
                    $response[$key] = $this->removeUnrequestedExpansions($response[$key], $requestedExpansions, $fetchedExpansions, ($prefix ?: '') . $relationKey . '.');
                }
            // just pass numeric arrays to the method again as they may contain additional relations in their values
            } else if (is_array($attr) && is_numeric($key)) {
                $response[$key] = $this->removeUnrequestedExpansions($response[$key], $requestedExpansions, $fetchedExpansions, $prefix);
            }
        }
        return $response;
    }
}

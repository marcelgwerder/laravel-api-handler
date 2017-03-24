<?php namespace Marcelgwerder\ApiHandler;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Response;

class Result
{
    /**
     * Parser instance.
     *
     * @var Marcelgwerder\ApiHandler\Parser
     */
    protected $parser;

    /**
     * Create a new result
     *
     * @param  Marcelgwerder\ApiHandler\Parser $parse
     * @return void
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Return a laravel response object including the correct status code and headers
     *
     * @return Illuminate\Support\Facades\Response
     */
    public function getResponse()
    {
        $headers = $this->getHeaders();

        if ($this->parser->mode == 'count') {
            return Response::json($headers, 200, $headers);
        } else {
            if ($this->parser->envelope) {
                return Response::json([
                    'meta' => $headers,
                    'data' => $this->getResult(),
                ], 200);
            } else {
                return Response::json($this->getResult(), 200, $headers);
            }

        }
    }

    /**
     * Return the query builder including the results
     *
     * @return Illuminate\Database\Query\Builder $result
     */
    public function getResult()
    {
        if ($this->parser->multiple) {
            $result = $this->cleanupRelationsOnModels($this->parser->builder->get());
        } else {
            $result = $this->cleanupRelations($this->parser->builder->first());
        }

        return $result;
    }

    /**
     * Get the query bulder object
     *
     * @return Illuminate\Database\Query\Builder
     */
    public function getBuilder()
    {
        return $this->parser->builder;
    }

    /**
     * Get the headers
     *
     * @return array
     */
    public function getHeaders()
    {
        $meta = $this->parser->meta;
        $headers = [];

        foreach ($meta as $provider) {
            if ($this->parser->envelope) {
                $headers[strtolower(str_replace('-', '_', preg_replace('/^Meta-/', '', $provider->getTitle())))] = $provider->get();
            } else {
                $headers[$provider->getTitle()] = $provider->get();
            }
        }

        return $headers;
    }

    /**
     * Get an array of meta providers
     *
     * @return array
     */
    public function getMetaProviders()
    {
        return $this->parser->meta;
    }

    /**
     * Get the mode of the parser
     *
     * @return string
     */
    public function getMode()
    {
        return $this->parser->mode;
    }

    /**
     * Cleanup the relations on a models array
     *
     * @param $models
     * @return array
     */
    public function cleanupRelationsOnModels($models)
    {
        $response = [];

        if ($models instanceof Collection) {
            foreach ($models as $model) {
                $response[] = $this->cleanupRelations($model);
            }
        }

        return $response;
    }

    /**
     * Cleanup the relations on a single model
     *
     * @param $model
     * @return mixed
     */
    public function cleanupRelations($model)
    {
        if (!($model instanceof Model)) {
            return $model;
        }

        // get the relations which already exists on the model (e.g. with $builder->with())
        $allowedRelations = $this->getRelationsRecursively($model);

        // parse the model to an array and get the relations, which got added unintentionally
        // (e.g. when accessing a relation in an accessor method)
        $response = $model->toArray();
        $loadedRelations = $this->getRelationsRecursively($model);

        // remove the unintentionally added relations from the response
        return $this->removeUnallowedRelationsFromResponse($response, $allowedRelations, $loadedRelations);
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
        $loadedRelations = $model->getRelations();
        $relations = [];

        foreach ($loadedRelations as $key => $relation) {
            $relations[] = ($prefix ?: '') . $key;
            $relationModel = $model->{$key};

            // if the relation is a collection, just use the first element as all elements of a relation collection are from the same model
            if ($relationModel instanceof Collection) {
                if (count($relationModel) > 0) {
                    $relationModel = $relationModel[0];
                } else {
                    continue;
                }
            }

            // get the relations of the child model
            if ($relationModel instanceof Model) {
                $relations = array_merge($relations, $this->getRelationsRecursively($relationModel, ($prefix ?: '') . $key . '.'));
            }
        }

        return $relations;
    }

    /**
     * Remove all relations which are in the $loadedRelations but not in $allowedRelations from the model array
     *
     * @param $response
     * @param $allowedRelations
     * @param $loadedRelations
     * @param null $prefix
     * @return mixed
     */
    protected function removeUnallowedRelationsFromResponse($response, $allowedRelations, $loadedRelations, $prefix = null)
    {
        foreach ($response as $key => $attr) {
            $relationKey = ($prefix ?: '') . $key;

            // handle associative arrays as they
            if (in_array($relationKey, $loadedRelations)) {
                if (!in_array($relationKey, $allowedRelations)) {
                    unset($response[$key]);
                } else if (is_array($attr)) {
                    $response[$key] = $this->removeUnallowedRelationsFromResponse($response[$key], $allowedRelations, $loadedRelations, ($prefix ?: '') . $relationKey . '.');
                }

            // just pass numeric arrays to the method again as they may contain additional relations in their values
            } else if (is_array($attr) && is_numeric($key)) {
                $response[$key] = $this->removeUnallowedRelationsFromResponse($response[$key], $allowedRelations, $loadedRelations, $prefix);
            }
        }

        return $response;
    }
}

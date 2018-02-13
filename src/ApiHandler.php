<?php

namespace Marcelgwerder\ApiHandler;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Marcelgwerder\ApiHandler\Resources\Json\Resource;
use Marcelgwerder\ApiHandler\Resources\Json\ResourceCollection;
use Marcelgwerder\ApiHandler\Parsers\Parser;

class ApiHandler
{
    /**
     * Untouched builder instance originally passed to the handler.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $originalBuilder;

    /**
     * Builder instance modified by the handler.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;

    /**
     * Request instance used to fetch the get parameters.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    protected $filters = [];

    protected $expansions = [];

    protected $sorts = [];

    protected $search = null;

    protected $resourceCollectionClass = ResourceCollection::class;

    protected $resourceClass = Resource::class;

    protected $parsers = [];

    protected $scopes = [];

    /**
     * Remembers whether the applied function has been called on the handler.
     *
     * @var bool
     */
    protected $applied = false;

    /**
     * Create a new api handler instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Builder $builder, Request $request)
    {
        $this->originalBuilder = $builder;
        $this->builder = clone $builder;
        $this->request = $request;
    }

    /**
     * Define which Builder/Model the handler will use.
     *
     * @param  mixed  $builder
     * @throws \InvalidArgumentException
     */
    public static function from($builder): self
    {
        if (is_string($builder)) {
            $builder = ($builder)::query();
        } else if (!$model instanceof Builder) {
            throw new InvalidArgumentException('The base builder must be either an instance of ' . Builder::class . ' or an absolute class string.');
        }

        return new static($builder, $request ?? request());
    }

    /**
     * Returns a resource object wrapping the data.
     *
     * @param  string $className
     * @return \Marcelgwerder\ApiHandler\Resources\Json\Resource
     */
    public function asResource(?string $className): Resource
    {
        $this->parse();

        if ($className) {
            if (is_subclass_of($className, ResourceCollection::class)) {
                $resourceCollectionClass = $className;
            } else {
                throw new InvalidArgumentException();
            }
        } else {
            $resourceCollectionClass = ResourceCollection::class;
        }

        return new $resourceCollectionClass($this->builder->first());
    }

    /**
     * Returns a resource collectio object wrapping the data.
     *
     * @param  string  $className
     * @return \Marcelgwerder\ApiHandler\Resources\Json\ResourceCollection
     */
    public function asResourceCollection(?string $className): ResourceCollection
    {
        $this->parse();

        if ($className) {
            if (is_subclass_of($className, Resource::class)) {
                $resourceClass = $className;
            } else {
                throw new InvalidArgumentException();
            }
        } else {
            $resourceClass = Resource::class;
        }

        return new ResourceCollection($this->builder->get(), $this->metafetcher);
    }

    /**
     * Get the modified builder instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getBuilder(): Builder
    {
        $this->apply();

        return $this->builder;
    }

    /**
     * Define the columns that should be searchable.
     *
     * @param  array  $searchables
     * @return void
     */
    public function searchable(array $searchables): self
    {

    }

    /**
     * Define the columns that should be filterable.
     *
     * @param  array  $filterables
     * @return void
     */
    public function filterable(array $filterables): self
    {

    }

    /**
     * Define the relations that should be expandable.
     *
     * @param  array  $expandables
     * @return void
     */
    public function expandable(array $expandables): self
    {

    }

    /**
     * Define the columns that should be sortable.
     *
     * @param  array  $sortables
     * @return void
     */
    public function sortable(array $sortables): self
    {

    }

    /**
     * Parse the given query parameters and return a ApiHandlerBag
     */
    public function parse(): void
    {

    }

    public function apply(): self
    {
        if ($this->applied) {
            return $this;
        }

        // Parse the request before we apply the result
        // of each parser to the builder instance.
        foreach ($this->parsers as $parser) {
            $parser->parse($this->request);
        }

        $this->builder->callScope(function (Builder $builder) use ($scope) {
            if ($scope instanceof Closure) {
                $scope($builder);
            } else if ($scope instanceof Scope) {
                $scope->apply($builder, $this->getModel());
            }
        });

        $this->applied = true;

        return $this;
    }

    public function applyScope($scope): self
    {
        if ($scope instanceof Closure || $scope instanceof Scope) {
            $this->scopes = $scope;
        } else {
            throw new InvalidArgumentException();
        }

        return $this;
    }

    // Setup methods

    /**
     * Register the default resource collection.
     *
     * @param  string  $resourceCollectionClass
     * @return void
     */
    public function registerResourceCollection(string $resourceCollectionClass): void
    {
        if (is_subclass_of($resourceCollectionClass, ResourceCollection::class)) {
            $this->resourceCollectionClass = $resourceCollectionClass;
        } else {
            throw new InvalidArgumentException();
        }
    }

    /**
     * Register the default resource.
     *
     * @param  string  $resourceClass
     * @return void
     */
    public function registerResource(string $resourceClass): void
    {
        if (is_subclass_of($resourceClass, Resource::class)) {
            $this->resourceClass = $resourceClass;
        } else {
            throw new InvalidArgumentException();
        }
    }

    /**
     * Register a new parser.
     *
     * @param  \Marcelgwerder\ApiHandler\Contracts\Parser  $parser
     * @return void
     */
    public function registerParser(string $parserClass): void
    {
        if (is_subclass_of($parserClass, Parser::class)) {
            $this->parsers[] = new $parserClass($this);
        } else {
            throw new InvalidArgumentException();
        }
    }

}

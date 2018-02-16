<?php

namespace Marcelgwerder\ApiHandler;

use function Marcelgwerder\ApiHandler\helpers\is_allowed_path;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Http\Request;
use Marcelgwerder\ApiHandler\Contracts\Filter;
use Marcelgwerder\ApiHandler\Database\Eloquent\Builder;
use Marcelgwerder\ApiHandler\Parsers\Parser;
use Marcelgwerder\ApiHandler\Resources\Json\Resource;
use Marcelgwerder\ApiHandler\Resources\Json\ResourceCollection;
use \Closure;
use \InvalidArgumentException;

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
     * @var \Marcelgwerder\ApiHandler\Database\Eloquent\Builder
     */
    protected $builder;

    /**
     * Request instance used to fetch the get parameters.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Filterable columns.
     *
     * @var array
     */
    protected $filterables = [];

    /**
     * Expandable relations.
     *
     * @var array
     */
    protected $expandables = [];

    /**
     * Sortable columns.
     *
     * @var array
     */
    protected $sortables = [];

    /**
     * Searchable columns.
     *
     * @var array
     */
    protected $searchables = [];

    /**
     * Resource collection used by default.
     *
     * @var string
     */
    protected $resourceCollectionClass = ResourceCollection::class;

    /**
     * Resource used by default.
     *
     * @var string
     */
    protected $resourceClass = Resource::class;

    /**
     * Filters registered.
     *
     * @var array
     */
    protected $filters = [];

    /**
     * Parsers registered.
     *
     * @var array
     */
    protected $parsers = [];

    /**
     * Scopes registered.
     *
     * @var array
     */
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
    public function __construct()
    {
        // ...
    }

    /**
     * Define which Builder/Model the handler will use.
     *
     * @param  mixed  $builder
     * @throws \InvalidArgumentException
     */
    public function from($builder): self
    {
        if (is_string($builder)) {
            $builder = ($builder)::query();

            $this->originalBuilder = $builder;
            $this->builder = new Builder($builder);
            $this->request = $request ?? request();
        } elseif (!$model instanceof EloquentBuilder) {
            throw new InvalidArgumentException('The base builder must be either an instance of ' . Builder::class . ' or an absolute class string.');
        }

        return $this;
    }

    /**
     * Returns a resource object wrapping the data.
     *
     * @param  string $className
     * @return \Marcelgwerder\ApiHandler\Resources\Json\Resource
     */
    public function asResource(string $className = null): Resource
    {
        $this->apply();

        if ($className) {
            if (is_subclass_of($className, Resource::class)) {
                $resourceClass = $className;
            } else {
                throw new InvalidArgumentException();
            }
        } else {
            $resourceClass = $this->resourceClass;
        }

        return new $resourceClass($this->builder->first());
    }

    /**
     * Returns a resource collectio object wrapping the data.
     *
     * @param  string  $className
     * @return \Marcelgwerder\ApiHandler\Resources\Json\ResourceCollection
     */
    public function asResourceCollection(string $className = null): ResourceCollection
    {
        $this->apply();

        if ($className) {
            if (is_subclass_of($className, ResourceCollection::class)) {
                $resourceCollectionClass = $className;
            } else {
                throw new InvalidArgumentException();
            }
        } else {
            $resourceCollectionClass = $this->resourceCollectionClass;
        }

        return new $resourceCollectionClass($this->builder->get());
    }

    /**
     * Returns the modified builder instance.
     *
     * @return \Marcelgwerder\ApiHandler\Database\Eloquent\Builder
     */
    public function asBuilder(): Builder
    {
        $this->apply();

        return $this->builder;
    }

    /**
     * Get the builder instance.
     *
     * @return \Marcelgwerder\ApiHandler\Database\Eloquent\Builder
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * Define the columns that should be searchable.
     *
     * @param  array|string|dynamic  $searchables
     * @return $this
     */
    public function searchable($searchables): self
    {
        $this->searchables = is_array($searchables) ? $searchables : func_get_args();

        return $this;
    }

    /**
     * Define the columns that should be filterable.
     *
     * @param  array|string|dynamic  $filterables
     * @return $this
     */
    public function filterable($filterables): self
    {
        $this->filterables = is_array($filterables) ? $filterables : func_get_args();

        return $this;
    }

    /**
     * Define the relations that should be expandable.
     *
     * @param  array|string|dynamic  $expandables
     * @return $this
     */
    public function expandable($expandables): self
    {
        $this->expandables = is_array($expandables) ? $expandables : func_get_args();

        return $this;
    }

    /**
     * Define the columns that should be sortable.
     *
     * @param  array|string|dynamic  $sortables
     * @return $this
     */
    public function sortable($sortables): self
    {
        $this->sortables = is_array($sortables) ? $sortables : func_get_args();

        return $this;
    }

    /**
     * Apply all the parameters of the request to the builder instance.
     *
     * @return $this
     */
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

        foreach ($this->parsers as $parser) {
            $parser->apply($this->builder, $this->builder->getModel());
        }

        // Add the scopes registered on the handler to the builder.
        foreach ($this->scopes as $scope) {
            $this->builder->withGlobalScope(spl_object_hash($scope), $scope);
        }

        $this->applied = true;

        return $this;
    }

    /**
     * Register a new scope for the current builder.
     * The scope will be applied after all the parsers have been applied.
     *
     * @param  mixed
     * @return $this
     */
    public function registerScope($scope): self
    {
        if ($scope instanceof Closure || $scope instanceof Scope) {
            $this->scopes[] = $scope;
        } else {
            throw new InvalidArgumentException('Random');
        }

        return $this;
    }

    /**
     * Register a new parser.
     *
     * @param  \Marcelgwerder\ApiHandler\Contracts\Parser  $parser
     * @return $this
     */
    public function registerParser(string $parserClass): self
    {
        if (is_subclass_of($parserClass, Parser::class)) {
            $this->parsers[] = new $parserClass($this);
        } else {
            throw new InvalidArgumentException();
        }

        return $this;
    }

    /**
     * Register a new filter.
     *
     * @param  string  $suffix
     * @param  mixed  $filter
     * @return $this
     */
    public function registerFilter(string $suffix, $filter): self
    {
        if ($filter instanceof Closure || $filter instanceof Filter) {
            $this->filters[$suffix] = $filter;
        } else {
            throw new InvalidArgumentException('Random');
        }

        return $this;
    }

    /**
     * Get a registered filter.
     *
     * @param  string  $suffix
     * @return void
     */
    public function getRegisteredFilter(string $suffix)
    {
        return $this->filters[$suffix] ?? null;
    }

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
     * Check whether a given column is filterable
     */
    public function isFilterable(string $column)
    {
        return is_allowed_path($column, $this->filterables);
    }

    public function isSortable(string $column)
    {
        return is_allowed_path($column, $this->sortables);
    }
}

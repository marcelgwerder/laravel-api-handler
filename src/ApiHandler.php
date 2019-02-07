<?php

namespace Marcelgwerder\ApiHandler;

use function Marcelgwerder\ApiHandler\helpers\is_allowed_path;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Http\Request;
use Marcelgwerder\ApiHandler\Contracts\ApiHandlerConfig;
use Marcelgwerder\ApiHandler\Contracts\Expandable;
use Marcelgwerder\ApiHandler\Contracts\Filter;
use Marcelgwerder\ApiHandler\Database\Eloquent\Builder;
use Marcelgwerder\ApiHandler\Parsers\PaginationParser;
use Marcelgwerder\ApiHandler\Parsers\Parser;
use Marcelgwerder\ApiHandler\Resources\Json\Resource;
use Marcelgwerder\ApiHandler\Resources\Json\ResourceCollection;
use \Closure;
use \InvalidArgumentException;

class ApiHandler
{
    /**
     * Base config array.
     * 
     * @var  array
     */
    protected static $baseConfig;
    
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
     * Selectable columns.
     *
     * @var array
     */
    protected $selectables = [];

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
     * Keeps track of the currently relevant config based on the config file.
     *
     * @var  \Illuminate\Contracts\Config\Repository
     */
    public $config;

    /**
     * Pagination parser which is applied differently than the rest.
     *
     * @var  \Marcelgwerder\ApiHandler\Parsers\Parser
     */
    protected $paginationParser;

    /**
     * Create a new api handler instance.
     *
     * @return void
     */
    public function __construct(ConfigContract $config)
    {
        $this->config = $config;
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
        } elseif ($builder instanceof Model) {
            $builder = $builder->newQuery();
        } elseif (!$builder instanceof EloquentBuilder) {
            throw new InvalidArgumentException('The base builder must be either an instance of ' . EloquentBuilder::class . ' or an absolute class string.');
        }

        $this->originalBuilder = $builder;
        $this->builder = new Builder($builder);
        $this->request = $request ?? request();

        // If the model implements a config, we merge it with the existing config
        // which is injected by the service provider.
        if ($builder->getModel() instanceof ApiHandlerConfig) {
            $modelConfig = array_dot($builder->getModel()->mergeApiHandlerConfig());

            foreach ($modelConfig as $key => $value) {
                if ($this->config->has($key)) {
                    $this->config->set($key, $value);
                }
            }
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

        if ($this->paginationParser !== null) {
            $pageSize = $this->paginationParser->pageSize;
            $page = $this->paginationParser->page;

            $results = $this->builder->paginate($pageSize, ['*'], 'page', $page);

            // Make sure all the query parameters are included in the pagination links.
            // They are needed because they contribute to the resulting query.
            $results->appends($this->request->except('page'));
        } else {
            $results = $this->builder->get();
        }

        return new $resourceCollectionClass($results);
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
     * Returns the builder instance.
     *
     * @return \Marcelgwerder\ApiHandler\Database\Eloquent\Builder
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * Returns the original builder instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getOriginalBuilder(): Builder
    {
        return $this->originalBuilder;
    }

    /**
     * Define the columns that should be sortable.
     *
     * @param  array|string|dynamic  $sortables
     * @return $this
     */
    public function sortable($sortables): self
    {
        $sortables = is_array($sortables) ? $sortables : [$sortables];

        $this->config->set('sortable', $sortables);

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
            $parser->parse($this->request, $this->parsers);
        }

        if ($this->paginationParser !== null) {
            $this->paginationParser->parse($this->request, $this->parsers);
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
    public function registerParser(string $parserClass, bool $pagination = false): self
    {
        if (is_subclass_of($parserClass, Parser::class)) {
            if ($pagination) {
                $this->paginationParser = new $parserClass($this);
            } else {
                $this->parsers[] = new $parserClass($this);
            }
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
     * Check whether the path is filterable.
     *
     * @param  string  $path
     * @return void
     */
    public function isFilterable(string $path)
    {
        return is_allowed_path($path, $this->config->get('filterable'));
    }

    /**
     * Check whether the path is sortable.
     *
     * @param  string  $path
     * @return boolean
     */
    public function isSortable(string $path)
    {
        $sortable = $this->config->get('sortable');
        $searchScoreColumn = $this->config->get('search_score_column');

        if ($searchScoreColumn !== null) {
            // We add the search score column to the resulting sortable list.
            // It is very common to sort by this column and should be possible by default.
            $sortable[] = $searchScoreColumn;
        }

        return is_allowed_path($path, $sortable);
    }

    /**
     * Check whether the path is selectable.
     *
     * @param  string  $path
     * @return boolean
     */
    public function isSelectable(string $path)
    {
        return is_allowed_path($path, $this->config->get('selectable'));
    }

    /**
     * Check whether the path is expandable.
     *
     * @param  string  $path
     * @return boolean
     */
    public function isExpandable(string $path)
    {
        if (!empty($this->config->get('expandable'))) {
            $expandables = $this->config->get('expandable');
        } else {
            $model = $this->builder->getModel();

            if ($model instanceof Expandable) {
                $expandables = $model->expandable();
            } else {
                $expandables = [];
            }
        }

        // Check if the path to the relation is allowed by the dev
        if (!is_allowed_path($path, $expandables)) {
            return false;
        }

        // Walks all the relations without doing anything on it, will return false
        // if a method does not exist or not return a relation.
        return $this->builder->walkRelations($path);
    }

    /**
     * Forward the method calls that match config keys to the config repository.
     *
     * @inheritDoc
     */
    public function __call($methodName, $arguments)
    {
        self::$baseConfig = self::$baseConfig ?: require __DIR__ . '/../config/apihandler.php';

        $keyName = snake_case($methodName);

        if (isset(self::$baseConfig[$keyName])) {
            $argumentCount = count($arguments);
            $currentValue = $this->config->get($keyName);
            $baseValueType = gettype(self::$baseConfig[$keyName]);

            if (count($arguments) === 0) {
                $this->config->set($keyName, true);
            } elseif ($baseValueType === 'array') {
                $this->config->set($keyName, $arguments);
            } else {
                $this->config->set($keyName, $arguments[0]);
            }

            return $this;
        } else {
            trigger_error('Call to undefined method ' . __CLASS__ . '::' . $methodName . '()', E_USER_ERROR);
        }
    }
}

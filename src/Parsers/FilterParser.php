<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Marcelgwerder\ApiHandler\Contracts\Filter;
use Marcelgwerder\ApiHandler\Exceptions\InvalidFilterException;
use \Closure;

class FilterParser extends Parser
{
    protected $filters = [];

    protected $filterMatch = '/^filter(?:$|-([a-z\-]+)$)/';

    public function parse(Request $request): ?array
    {
        $parameters = array_filter($request->all(), function ($parameterName) {
            return preg_match($this->filterMatch, $parameterName);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($parameters as $parameterName => $parameterValue) {
            preg_match($this->filterMatch, $parameterName, $matches);
            $filterSuffix = $matches[1] ?? 'default';
            $filter = $this->handler->getRegisteredFilter($filterSuffix);

            if ($filter === null) {
                throw new InvalidFilterException('No filter registered under the suffix "' . $filterSuffix . '"');
            }

            $this->parseParameter($filter, $parameterValue);
        }

        return $this->filters;
    }

    public function apply(Builder $builder, Model $model)
    {
        if (!isset($this->filters['.'])) {
            return false;
        }

        foreach ($this->filters['.'] ?? [] as $parameters) {
            $filter = array_shift($parameters);

            $builder->callScope(function (...$parameters) use ($filter) {
                if ($filter instanceof Closure) {
                    $filter(...$parameters);
                } elseif ($filter instanceof Filter) {
                    $filter->apply(...$parameters);
                }
            }, $parameters);
        }
    }

    protected function parseParameter($filter, $parameterValue)
    {
        $builder = $this->handler->getBuilder();

        if (is_array($parameterValue)) {
            $dottedParams = array_dot($parameterValue);

            foreach ($dottedParams as $columnPath => $value) {
                if ($this->handler->isFilterable($columnPath)) {
                    if (strpos($columnPath, '.')) {
                        preg_match('/^(.+)(?:\.(.+))$/', $columnPath, $pathSegments);
                        list(, $parentPath, $column) = $pathSegments;
                    } else {
                        $parentPath = '.';
                        $column = $columnPath;
                    }

                    $this->filters[$parentPath] = $this->filters[$parentPath] ?? [];
                    $this->filters[$parentPath][] = [$filter, $value, $column];
                } else {
                    throw new InvalidFilterException('Filter path "' . $columnPath . '" is not allowed on this endpoint.');
                }
            }
        } else {
            $this->filters['.'][] = [$filter, $parameterValue, null];
        }
    }
}

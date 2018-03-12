<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Marcelgwerder\ApiHandler\Exceptions\InvalidPaginationException;

class PaginationParser extends Parser
{
    /**
     * The limit that has to be applied to the query.
     *
     * @var int
     */
    protected $limit;

    /**
     * The offset that has to be applied to the query.
     *
     * @var int
     */
    protected $offset;

    /**
     * Parse the "expand" query parameter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function parse(Request $request): ?array
    {
        $this->limit = $this->parseLimit($request);
        $this->offset = $this->parseOffset($request, $this->limit);

        return [
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($this->limit !== null) {
            $builder->limit($this->limit);

            if ($this->offset !== null) {
                $builder->offset($this->offset);
            }
        }
    }

    /**
     * Parse the limit for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function parseLimit(Request $request): ?int
    {
        if ($request->has('limit')) {
            $limit = $request->limit;
        } elseif ($request->has('pageSize')) {
            $limit = $request->pageSize;
        } else {
            $limit = $this->handler->config->get('default_page_size');
        }

        if (!is_numeric($limit)) {
            throw new InvalidPaginationException('The page size or limit is expected to be numeric, "' . $limit . '" given.');
        } elseif ($limit > $this->handler->config->get('max_page_size')) {
            throw new InvalidPaginationException('The page size or limit is expected to be smaller than ' . $this->handler->config->get('max_page_size') . ', ' . $limit . ' given.');
        }

        return (int) $limit;
    }

    /**
     * Parse the offset for the given request and limit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $limit
     * @return int
     */
    protected function parseOffset(Request $request, int $limit): ?int
    {
        if ($request->has('offset')) {
            $offset = $request->offset;
        } elseif ($request->has('page')) {
            $offset = ((int) $request->page - 1) * $limit;
        } else {
            $offset = null;
        }

        if (!is_numeric($offset) && $offset !== null) {
            throw new InvalidPaginationException('The offset is expected to be numeric, "' . $offset . '" given.');
        }

        return $offset;
    }
}

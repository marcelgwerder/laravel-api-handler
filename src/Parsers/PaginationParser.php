<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Marcelgwerder\ApiHandler\Exceptions\InvalidPaginationException;

class PaginationParser extends Parser
{
    /**
     * The pageSize that has to be applied to the query.
     *
     * @var int
     */
    public $pageSize;

    /**
     * The page that has to be applied to the query.
     *
     * @var int
     */
    public $page;

    /**
     * Parse the pagination query parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function parse(Request $request): ?array
    {
        $this->pageSize = $this->parsePageSize($request);
        $this->page = $this->parsepage($request, $this->pageSize);

        return [
            'pageSize' => $this->pageSize,
            'page' => $this->page,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        // No implementation since pagination is handled by the Laravel pagination.
    }

    /**
     * Parse the pageSize for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function parsePageSize(Request $request): ?int
    {
        if ($request->has('pageSize')) {
            $pageSize = $request->pageSize;
        } else {
            $pageSize = $this->handler->config->get('default_page_size');
        }

        if (! is_numeric($pageSize)) {
            throw new InvalidPaginationException('The page size or is expected to be numeric, "'.$pageSize.'" given.');
        } elseif ($pageSize > $this->handler->config->get('max_page_size')) {
            throw new InvalidPaginationException('The page size or is expected to be smaller than '.$this->handler->config->get('max_page_size').', '.$pageSize.' given.');
        }

        return (int) $pageSize;
    }

    /**
     * Parse the page for the given request and pageSize.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $pageSize
     * @return int
     */
    protected function parsePage(Request $request, int $pageSize): ?int
    {
        $page = $request->get('page', null);

        if (! is_numeric($page) && $page !== null) {
            throw new InvalidPaginationException('The page is expected to be numeric, "'.$page.'" given.');
        }

        return (int) $page;
    }
}

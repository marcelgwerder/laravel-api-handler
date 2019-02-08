<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Marcelgwerder\ApiHandler\Exceptions\InvalidSortException;

class SortParser extends Parser
{
    protected $sorts = [];

    public function parse(Request $request): ?array
    {
        $sort = $request->input('sort');

        if (empty($sort)) {
            return null;
        }

        foreach (explode(',', $sort) as $sort) {
            $columnPath = ltrim($sort, '-');
            $direction = strpos($sort, '-') === 0 ? 'desc' : 'asc';

            if ($this->handler->isSortable($columnPath)) {
                $this->sorts[] = [
                    $columnPath,
                    $direction,
                ];
            } else {
                throw new InvalidSortException('Sort path "'.$columnPath.'" is not allowed on this endpoint.');
            }
        }

        return $this->sorts;
    }

    public function apply(Builder $builder, Model $model)
    {
        foreach ($this->sorts as $sort) {
            list($column, $direction) = $sort;

            $builder->orderBy($column, $direction);
        }
    }
}

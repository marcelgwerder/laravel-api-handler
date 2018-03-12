<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Marcelgwerder\ApiHandler\Exceptions\InvalidSelectException;

class SelectParser extends Parser
{
    protected $columns = [];

    /**
     * Parse the "expand" query parameter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function parse(Request $request): ?array
    {
        if (!$request->has('select')) {
            return null;
        }

        $columnsParameter = $request->input('select');

        foreach (explode(',', $columnsParameter) as $column) {
            if ($this->handler->isSelectable($column)) {
                if ($column !== '') {
                    $this->columns[] = trim($column);
                }
            } else {
                throw new InvalidSelectException('Select path "' . $column . '" is not allowed on this endpoint.');
            }
        }

        return $this->columns;
    }

    /**
     * Apply the "expand" query parameter.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @param  \Illuminate\Database\Eloquent\Model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (!empty($this->columns)) {
            $builder->addSelect($this->columns);
        }
    }

    /**
     * Get the fields parsed from the parameter.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}

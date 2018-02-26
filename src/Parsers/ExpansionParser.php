<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Marcelgwerder\ApiHandler\Exceptions\InvalidExpandException;
use function Marcelgwerder\ApiHandler\helpers\unqualify_column;

class ExpansionParser extends Parser
{
    protected $expansions = [];

    /**
     * Parse the "expand" query parameter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function parse(Request $request): ?array
    {
        if(!$request->has('expand')) {
            return null;
        }
        
        $expand = $request->input('expand');

        $builder = $this->handler->getBuilder();

        preg_match_all('/([^\[,]+)(?:\[([^\]]*)\])?,?/', $expand, $matches);

        $expansions = [];
        foreach ($matches[1] as $number => $expansion) {
            $columns = explode(',', $matches[2][$number]);

            if (!$this->handler->isExpandable($expansion)) {
                throw new InvalidExpandException('Expansion path "' . $expansion . '" is not allowed on this endpoint.');
            }

            $builder->walkRelations($expansion, (function (Relation $relation, string $path, ?string $parentPath) use (&$expansions, $expansion, $columns) {
                $parentPath = $parentPath === null ? '.' : $parentPath;

                // Check whether we're on the last relation of the path.
                // If yes, we also add the columns selected.
                if ($expansion === $path) {
                    $columns = array_map(function ($column) use ($relation) {
                        return $relation->getRelated()->getTable() . '.' . $column;
                    }, $columns);
                } else {
                    $columns = [];
                }

                $required = $this->determineRequiredColumns($relation);

                $expansions[$parentPath] = array_unique(array_merge($expansions[$parentPath] ?? [], $required['parent']));
                $expansions[$path] = array_unique(array_merge($expansions[$path] ?? [], $required['related'], $columns));
            })->bindTo($this));
        }

        return $this->expansions = $expansions;
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
        $expansions = $this->expansions;
        $baseColumns = array_pull($expansions, '.');

        $withs = array_map(function ($columns) {
            return function ($query) use ($columns) {
                $query->addSelect($columns);
            };
        }, $expansions);

        if (!empty($baseColumns)) {
            $builder->addSelect($baseColumns);
        }

        $builder->with($withs);
    }

    protected function determineRequiredColumns(Relation $relation): array
    {
        if ($relation instanceof BelongsTo) {
            $parent = [
                $relation->getQualifiedParentKeyName(),
            ];

            $related = [
                $relation->getQualifiedForeignKey(),
            ];
        } elseif ($relation instanceof HasMany || $relation instanceof HasOne) {
            $parent = [
                $relation->getQualifiedParentKeyName(),
            ];

            $related = [
                $relation->getQualifiedForeignKeyName(),
            ];
        } elseif ($relation instanceof BelongsToMany) {
            $parent = [
                $relation->getQualifiedParentKeyName(),
            ];
            $related = [
                $relation->getRelated()->getQualifiedKeyName(),
            ];
        } elseif ($relation instanceof HasManyThrough) {
            $parent = [
                $relation->getQualifiedLocalKeyName(),
            ];
        } elseif ($relation instanceof MorphMany || $relation instanceof MorphTo) {
            $parent = [
                $relation->getQualifiedParentKeyName(),
            ];

            $related = [
                $relation->getQualifiedForeignKeyName(),
                $relation->getRelated()->getTable() . '.' . $relation->getMorphType(),
            ];
        } else {
            throw ApiHandlerException('Relation "'.get_class($relation).'" is not supported.');
        }

        return [
            'parent' => $parent,
            'related' => $related,
        ];
    }

}

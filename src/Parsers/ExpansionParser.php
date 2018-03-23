<?php

namespace Marcelgwerder\ApiHandler\Parsers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Marcelgwerder\ApiHandler\Exceptions\InvalidExpandException;

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
        if (!$request->has('expand')) {
            return null;
        }

        $expand = $request->input('expand');

        $builder = $this->handler->getBuilder();

        preg_match_all('/([^\[,]+)(?:\[([^\]]*)\])?,?/', $expand, $matches);

        $expansions = [];
        foreach ($matches[1] as $number => $expansion) {
            $columns = $matches[2][$number] !== '' ? explode(',', $matches[2][$number]) : [];

            if (!$this->handler->isExpandable($expansion)) {
                throw new InvalidExpandException('Expansion path "' . $expansion . '" is not allowed on this endpoint.');
            }

            $builder->walkRelations($expansion, (function (Relation $relation, string $path, ?string $parentPath) use (&$expansions, $expansion, $columns) {
                $parentPath = $parentPath === null ? '.' : $parentPath;

                // Check whether we're on the last relation of the path.
                // If yes, we also add the columns selected.
                // Additionally exclude polymorphic relations because selecting fields
                // is currently not supported on those relations.
                if ($expansion === $path && $this->isPolymorphic($relation) && !empty($columns)) {
                    throw new InvalidExpandException('Expansion "' . $expansion . '" does not accept columns since it is polymorphic.');
                } elseif ($expansion === $path) {
                    $columns = array_map(function ($column) use ($relation) {
                        return $relation->getRelated()->getTable() . '.' . $column;
                    }, $columns);
                    $column = [];
                } else {
                    $columns = [];
                }

                $required = $this->determineRequiredColumns($relation);
                $requiredRelated = !empty($columns) ? $required['related'] : [];

                $expansions[$parentPath] = array_unique(array_merge($expansions[$parentPath] ?? [], $required['parent']));
                $expansions[$path] = array_unique(array_merge($expansions[$path] ?? [], $requiredRelated, $columns));
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
                if (!empty($columns)) {
                    $query->addSelect($columns);
                }
            };
        }, $expansions);

        // Only add the required select if there is already a column in the list.
        if (!empty($baseColumns) && !empty($builder->getQuery()->columns)) {
            $builder->addSelect($baseColumns);
        }

        $builder->with($withs);
    }

    /**
     * Check if a relation is polymorphic
     *
     * @param  Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @return bool
     */
    protected function isPolymorphic(Relation $relation): bool
    {
        return $relation instanceof MorphMany || $relation instanceof MorphToMany || $relation instanceof MorphTo;
    }

    /**
     * Determine which of the parent and related columns are required so
     * the related models can be properly matched to the parent.
     *
     * @param  Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @return array
     */
    protected function determineRequiredColumns(Relation $relation): array
    {
        if ($relation instanceof MorphMany) {
            $parent = [
                $relation->getQualifiedParentKeyName(),
            ];

            $related = [
                $relation->getRelated()->getQualifiedKeyName(),
            ];
        } elseif ($relation instanceof MorphTo) {
            $parent = [
                $relation->getQualifiedForeignKey(),
                $relation->getRelated()->getTable() . '.' . $relation->getMorphType(),
            ];

            $related = [
                $relation->getQualifiedParentKeyName(),
            ];
        } elseif ($relation instanceof MorphToMany) {
            $parent = [
                $relation->getQualifiedParentKeyName(),
            ];
            $related = [
                $relation->getRelated()->getQualifiedKeyName(),
            ];
        } elseif ($relation instanceof BelongsTo) {
            $parent = [
                $relation->getQualifiedForeignKey(),
            ];

            $related = [
                $relation->getQualifiedOwnerKeyName(),
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

            $related = [
                $relation->getQualifiedForeignKeyName(),
            ];
        } else {
            throw new InvalidExpandException('Relation "' . get_class($relation) . '" is not supported.');
        }

        return [
            'parent' => $parent,
            'related' => $related,
        ];
    }

}

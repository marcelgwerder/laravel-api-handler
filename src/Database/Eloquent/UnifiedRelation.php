<?php

namespace Marcelgwerder\ApiHandler\Database\Eloquent;

class UnifiedRelation
{

    protected $relation;

    public function __construct(Relation $relation)
    {
        $this->relation = $relation;
    }

    public function getParentKey(): string
    {

    }

    public function getRelationKey(): string
    {

    }
}

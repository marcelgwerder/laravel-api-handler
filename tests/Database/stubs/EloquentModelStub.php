<?php

namespace Foo\Bar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Marcelgwerder\ApiHandler\Contracts\Expandable;

class EloquentModelStub extends Model implements Expandable
{

    public function hasOneRelation(): Relation
    {
        return $this->hasOne(RelatedEloquentModelStub::class);
    }

    public function expandable(): array
    {
        return ['*'];
    }
}

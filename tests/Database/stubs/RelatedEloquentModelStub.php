<?php

namespace Foo\Bar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Marcelgwerder\ApiHandler\Contracts\Expandable;

class RelatedEloquentModelStub extends Model implements Expandable
{
    protected $table = 'related_model';

    public function belongsToRelation(): Relation
    {
        return $this->belongsTo(EloquentModelStub::class, 'foreign_key', 'other_key');
    }

    public function morphToRelation(): Relation
    {
        return $this->morphTo($name = null, $type = null, $id = null, $ownerKey = 'wefewfwf');
    }

    public function morphedByManyRelation(): Relation
    {
        return $this->morphedByMany(EloquentModelStub::class, 'relatable');
    }

    public function expandable(): array
    {
        return ['*'];
    }
}

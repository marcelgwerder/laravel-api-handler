<?php

namespace Foo\Bar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Marcelgwerder\ApiHandler\Contracts\Expandable;

class EloquentModelStub extends Model implements Expandable
{

    protected $table = 'model';

    public function hasOneRelation(): Relation
    {
        return $this->hasOne(RelatedEloquentModelStub::class, 'foreign_key', 'local_key');
    }

    public function hasManyRelation(): Relation
    {
        return $this->hasMany(RelatedEloquentModelStub::class, 'foreign_key', 'local_key');
    }

    public function belongsToManyRelation(): Relation
    {
        return $this->belongsToMany(RelatedEloquentModelStub::class, 'pivot_table', 'local_key', 'other_key');
    }

    public function hasManyThroughRelation(): Relation
    {
        return $this->hasManyThrough(
            RelatedEloquentModelStub::class, IntermediateEloquentModelStub::class,
            'intermediate_foreign_key', 'related_foreign_key', 'local_key'
        );
    }

    public function morphManyRelation(): Relation
    {
        return $this->morphMany(RelatedEloquentModelStub::class, 'morphToRelation');
    }

    public function morphToManyRelation(): Relation
    {
        return $this->morphToMany(RelatedEloquentModelStub::class, 'relatable');
    }

    public function expandable(): array
    {
        return ['*'];
    }
}

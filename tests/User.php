<?php
/**
 * This model is only used for testing purpose
 */

class User extends \Illuminate\Database\Eloquent\Model
{
    protected $connection = 'mysql';

    /**
     * @Relation
     */
    public function comments()
    {
        return $this->hasMany('Comment');
    }
}

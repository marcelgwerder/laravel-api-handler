<?php
/**
 * This model is only used for testing purpose
 */

class Comment extends \Illuminate\Database\Eloquent\Model
{
    protected $connection = 'mysql';

    /**
     * @Relation
     */
    public function post()
    {
        return $this->belongsTo('Post');
    }

    /**
     * @Relation
     */
    public function user()
    {
        return $this->belongsTo('User');
    }
}

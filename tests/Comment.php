<?php
/**
 * This model is only used for testing purpose
 */

class Comment extends \Illuminate\Database\Eloquent\Model
{
	protected $connection = 'mysql';

	public function post()
	{
		return $this->belongsTo('Post');
	}

	public function user()
	{
		return $this->belongsTo('User');
	}
}
<?php 
/**
 * This model is only used for testing purpose
 */

class Post extends \Illuminate\Database\Eloquent\Model
{
	protected $connection = 'mysql';

	/**
	 * @Relation
	 */
	public function comments()
	{
		return $this->hasMany('Comment', 'customfk_post_id');
	}
}
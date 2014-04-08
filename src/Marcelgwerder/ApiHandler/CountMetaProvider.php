<?php namespace Marcelgwerder\ApiHandler;

class CountMetaProvider extends MetaProvider
{
	/**
	 * Query builder object
	 *
	 * @var mixed
	 */
	protected $builder;

	public function __construct($title, $builder)
	{
		//Remove offset from builder because a count doesn't work in combination with an offset
		$builder->offset(0);
		
		$this->builder = $builder;
		$this->title = $title;
	}

	/**
	 * Get the meta information
	 * 
	 * @return string
	 */
	public function get()
	{
		return intval($this->builder->count());
	}
}

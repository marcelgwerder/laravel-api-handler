<?php namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\Facades\Input;

class ApiHandler 
{
	/**
	 * Returns a Parser objects which then provides the parsing
	 * 
	 * @param  Illuminate\Database\Query\Builder 	$queryBuilder 
	 * @param  array 								$queryParams  	
	 * @return Marcelgwerder\ApiHandler\Parser               		
	 */
	public static function parse($queryBuilder, $queryParams = false) 
	{
		if(!$queryParams) $queryParams = Input::get();

		return new Parser($queryBuilder, $queryParams);
	}
}
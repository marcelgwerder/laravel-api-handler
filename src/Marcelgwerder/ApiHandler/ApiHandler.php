<?php namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\Facades\Input;

class ApiHandler 
{
	public static function parse($queryBuilder, $queryParams = false) 
	{
		if(!$queryParams) $queryParams = Input::get();

		return new Parser($queryBuilder, $queryParams);
	}
}
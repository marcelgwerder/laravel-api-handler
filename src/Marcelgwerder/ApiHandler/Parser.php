<?php namespace Marcelgwerder\ApiHandler;

class Parser
{
	
	private $queryBuilder;
	private $queryBuilderOriginal;
	private $queryParams;

	public function __construct($queryBuilder, $queryParams)
	{
		$this->queryBuilder = $queryBuilder;
		$this->queryParams = $queryParams;
	}

	public function single($identification) 
	{
		if(is_numeric($identification))
		{
			$this->queryBuilder->where('id', $identification);
		}
		else if(is_array($identification))
		{
			$this->queryBuilder->where($identification);
		}


		return new Result($queryBuilder, $meta);
	}	

	/**
	 * Handles the access to a collection (multiple datasets)
	 * 
	 * @param  array  							$fullTextSearchColumns 	Columns which should be searched within the fulltext search
	 * @return Marcelgwerder\ApiHandler\Result 	  						Result object which provides access to several getter functions
	 */
	public function multiple($fullTextSearchColumns = array()) 
	{
		
		$with = array();
		$fields = array();

		
		//Parse and apply with elements using the Laravel "with" function 
		if(isset($this->queryParams['with']))
		{
			$with = $this->parseWith($this->queryParams['with'], $fields);
			call_user_func_array(array($this->queryBuilder, 'with'), $with);
		}
		
		//Parse and apply sort elements using the laravel "orderBy" function
		if(isset($this->queryParams['sort']))
		{
			$sort = $this->parseSort($this->queryParams['sort']);

			foreach ($sort as $pair) 
			{
				call_user_func_array(array($this->queryBuilder, 'orderBy'), $pair);
			}
		}

		//Parse and apply offset using the laravel "skip" function
		if(isset($this->queryParams['offset']))
		{
			$offset = intval($this->queryParams['offset']);
			$this->queryBuilder->skip($offset);
		}

		//Parse and apply limit using the laravel "take" function
		if(isset($this->queryParams['limit']))
		{
			$limit = intval($this->queryParams['limit']);
			$this->queryBuilder->take($limit);
		}

		//Parse and apply field elements using the laravel "select" function
		//The needed fields for the with function (Primary and foreign keys) have to be added accordingly
		if(isset($this->queryParams['fields']))
		{
			$fields = $this->parseFields($this->queryParams['fields'], $with);
			call_user_func_array(array($this->queryBuilder, 'select'), $fields);
		}

		//Parse and apply the filters using the different laravel "where" functions
		//Every parameter that has not a predefined functionality gets parsed as a filter
		$filterParams = array_diff_key(
			$this->queryParams, 
			array('fields' => false, 'sort' => false, 'limit' => false, 'offset' => false, 'meta' => false, 'with' => false)
		);

		if(count($filterParams) > 0)
		{
			$filters = $this->parseFilter($filterParams);
			foreach ($filters as $filter) 
			{
				$this->queryBuilder->where(function($query) use($filter)
	            {
	                call_user_func_array(array($query, 'where'), $filter);
	            });
			}
		}

		$metaData = array();

		return new Result('multiple', $this->queryBuilder, $this->queryBuilderOriginal, $metaData);
	}

	/**
	 * Parses the fields parameter and returns an array of fields
	 * 
	 * @param  string 	$fieldsParam 	Query Parameter "fields"
	 * @return array 	$fields      	Array with all fields
	 */
	private function parseFields($fieldsParam, $with)
	{
		$fields = explode(',', $fieldsParam);
		$fields = array_map('trim', $fields);

		return $fields;
	}

	private function parseWith($withParam)
	{
		$with = explode(',', $withParam);
		$with = array_map('trim', $with);

		return $with;
	}

	/**
	 * Parses the sort param and determines whether the sorting is ascending or descending.
	 * A descending sort has a leading "-".
	 * 
	 * @param  string 	$sortParam 	String containg the sorting 
	 * @return array[]  $sort       Multidimensional array containing all sort pairs (column and direction)
	 */
	private function parseSort($sortParam)
	{
		$sort = array();
		$sortElems = explode(',', $sortParam);

		foreach($sortElems as $sortElem) 
		{
			//Check if ascending or derscenting(-) sort
			if(preg_match('/^-.+/', $sortElem)) 
			{
				$direction = 'desc';
			}
			else 
			{
				$direction = 'asc';
			}

			$sort[] = array(preg_replace('/^-/', '', $sortElem), $direction);

		}

		return $sort;
	}

	/**
	 * Parses the remaining filter params
	 * 
	 * @param  array 		$filterParams 	Array of all non predefined parameters  
	 * @return array[]   	$filters       	Array of all filters and their settings
	 */
	private function parseFilter($filterParams) 
	{
		$filters = array();

		foreach ($filterParams as $filterParamKey => $filterParamValue) 
		{
			$comparator = '=';
			$comparatorMatches = array();

			preg_match_all('/-(st|gt|min|max|lk)?$/', $filterParamKey, $comparatorMatches);

			if(!isset($comparatorMatches[0][0]))
			{
				$column = $filterParamKey;
				$comparator = '=';
			}
			else
			{
				$comparatorSearch = array('st', 'gt', 'min', 'max', 'lk', '');
				$comparatorReplace = array('<', '>', '>=', '<=', 'LIKE', '=');

				$column = str_replace($comparatorMatches[0][0], '', $filterParamKey);
				$comparator = str_replace($comparatorSearch, $comparatorReplace, $comparatorMatches[1][0]);
			}

			$filters[] = array($column, $comparator, $filterParamValue);  
		}

		return $filters;
	}

	private function parseFulltextSearch($qParam, $search)
	{
		return $search;
	}

	private function parseMeta($metaParam)
	{
		return $meta;
	}
}
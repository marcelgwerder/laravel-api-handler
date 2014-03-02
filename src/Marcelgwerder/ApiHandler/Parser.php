<?php namespace Marcelgwerder\ApiHandler;

use \Illuminate\Database\Eloquent\Relations\HasMany;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parser
{

	/**
	 * The builder Instance.
	 *
	 * @var mixed
	 */
	public $builder;

	/**
	 * The original builder Instance.
	 *
	 * @var mixed
	 */
	public $originalBuilder;

	/**
	 * The parsed meta information
	 *
	 * @var array
	 */
	public $meta = array();

	/**
	 * If the parser works on multiple datasets
	 *
	 * @var boolean
	 */
	public $multiple;

	/**
	 * The base query builder instance.
	 *
	 * @var \Illuminate\Database\Query\Builder
	 */
	protected $query;

	/**
	 * The http query params.
	 *
	 * @var array
	 */
	protected $params;

	/**
	 * All given fields
	 *
	 * @var array
	 */
	protected $additionalFields = array();

	/**
	 * If builder is an eloquent builder or not
	 *
	 * @var boolean
	 */
	protected $isEloquentBuilder = false;

	/**
	 * If builder is an query builder or not
	 *
	 * @var boolean
	 */
	protected $isQueryBuilder = false;

	/**
	 * Instantiate the Parser class
	 * 
	 * @param mixed 	$builder
	 * @param array 	$params
	 */
	public function __construct($builder, $params)
	{

		$this->builder = $builder;
		$this->params = $params;

		$isEloquentModel = is_subclass_of($builder, '\Illuminate\Database\Eloquent\Model');
		$this->isEloquentBuilder = $builder instanceof \Illuminate\Database\Eloquent\Builder;
		$this->isQueryBuilder = $builder instanceof \Illuminate\Database\Query\Builder;

		if($this->isEloquentBuilder) 
		{
   			$this->query = $builder->getQuery();
		}
		else if($isEloquentModel)
		{
			//Convert the model to a builder object
			$this->builder = $builder->newQuery();

			$this->query = $this->builder->getQuery();

			$this->isEloquentBuilder = true;
		}
		else if($this->isQueryBuilder) 
		{
			$this->query = $builder;
		}
		else
		{
			throw new \InvalidArgumentException('The builder argument has to be of type Illuminate\Database\Eloquent\Builder or Illuminate\Database\Query\Builder');
		}

		$this->originalBuilder = clone $this->builder;
	}

	/**
	 * Parse the query parameters with the given options.
	 * Either for a single dataset or multiple.
	 * 
	 * @param  mixed  	$options  
	 * @param  boolean 	$multiple
	 * @return void    
	 */
	public function parse($options, $multiple = false)
	{
		$this->multiple = $multiple;

		if($multiple)
		{
			$fullTextSearchColumns = $options;

			//Parse and apply sort elements using the laravel "orderBy" function
			if(isset($this->params['sort']))
			{
				$this->parseSort($this->params['sort']);
			}

			//Parse and apply offset using the laravel "offset" function
			if(isset($this->params['offset']))
			{
				$offset = intval($this->params['offset']);
				$this->query->offset($offset);
			}

			//Parse and apply limit using the laravel "limit" function
			if(isset($this->params['limit']))
			{
				$limit = intval($this->params['limit']);
				$this->query->limit($limit);
			}

			//Parse and apply the filters using the different laravel "where" functions
			//Every parameter that has not a predefined functionality gets parsed as a filter
			$filterParams = array_diff_key(
				$this->params, 
				array('fields' => false, 'sort' => false, 'limit' => false, 'offset' => false, 'meta' => false, 'with' => false, 'q' => false)
			);

			if(count($filterParams) > 0)
			{
				$this->parseFilter($filterParams);
			}

			//Parse an apply the fulltext search using the different laravel "where" functions
			//The fulltext search is only applied to the columns passed by $fullTextSearchColumns
			if(isset($this->params['q']))
			{
				$this->parseFulltextSearch($this->params['q'], $fullTextSearchColumns);
			}
		} 
		else
		{
			$identification = $options;

			if(is_numeric($identification))
			{
				$this->query->where('id', $identification);
			}
			else if(is_array($identification))
			{
				$this->query->where($identification);
			}
		}

		//Parse and apply field elements using the laravel "select" function
		//The needed fields for the with function (Primary and foreign keys) have to be added accordingly
		if(isset($this->params['fields']))
		{
			$this->parseFields($this->params['fields']);
		}

		//Parse and apply with elements using the Laravel "with" function 
		if(isset($this->params['with']) && $this->isEloquentBuilder)
		{
			$this->parseWith($this->params['with']);
		}		

		//Parse and apply the meta data
		if(isset($this->params['meta']))
		{
			$this->parseMeta($this->params['meta']);
		}

		if($this->isEloquentBuilder)
		{
			//Attach the query builder object back to the eloquent builder object
			$this->builder->setQuery($this->query);
		}
	}

	/**
	 * Parse the fields parameter and return an array of fields
	 * 
	 * @param  string 	$fieldsParam 
	 * @return void
	 */
	protected function parseFields($fieldsParam)
	{
		$fields = array();

		foreach(explode(',', $fieldsParam) as $field)
		{
			//Only add the fields that are on the base resource
			if(strpos($field, '.') === false)
			{
				$fields[] = trim($field);
			}
			else 
			{
				$this->additionalFields[] = trim($field);
			}
		}

		$this->query->select($fields);
	}

	/**
	 * Parse the with parameter
	 * 
	 * @param  string 	$withParam 
	 * 
	 * @return void
	 */
	protected function parseWith($withParam)
	{
		$fields = $this->query->columns;
		$fieldsCount = count($fields);
		$baseModel = $this->builder->getModel();

		$withHistory = array();

		foreach(explode(',', $withParam) as $with)
		{
			//Use ArrayObject to be able to copy the array (for array_splice)
			$parts = new \ArrayObject(explode('.', $with));
			$lastKey = count($parts)-1;

			for($i = 0; $i <= $lastKey; $i++)
			{
				$part = $parts[$i];
				$partsCopy = $parts->getArrayCopy();

				//Get the previous history path (e.g. if current is a.b.c the previous is a.b)
				$previousHistoryPath = implode('.', array_splice($partsCopy, 0, $i));
				//Get the current history part based on the previous one
				$currentHistoryPath = $previousHistoryPath ? $previousHistoryPath.'.'.$part : $part;

				//Create new history element
				if(!isset($withHistory[$currentHistoryPath]))
				{
					$withHistory[$currentHistoryPath] = array(
						'fields' => array()
					);
				}

				//Get all given fields related to the current part
				$withHistory[$currentHistoryPath]['fields'] = array_filter($this->additionalFields, function($val) use($part) {
					return preg_match('/'.$part.'\..+$/', $val);
				});

				if(!isset($previousModel))
				{
					$previousModel = $baseModel;
				}
				
				$relation = call_user_func(array($previousModel, $part));


				$model = $relation->getModel();
				
				$primaryKey = 'id';
				$foreignKey = $relation->getForeignKey();

				$relationType = $this->getRelationType($relation);

				//Switch keys according to the type of relationship
				if($relationType == 'HasMany')
				{
					$firstKey = $primaryKey;
					$secondKey = $foreignKey;
				}
				else if($relationType == 'BelongsTo')
				{
					$firstKey = $foreignKey;
					$secondKey = $primaryKey;
				}
				
				//Check if we're on level 1 (e.g. a and not a.b)
				if($previousHistoryPath == '')
				{
					if($fieldsCount > 0 && !in_array($primaryKey, $fields))
					{
						$fields[] = $firstKey;
					}
				}
				else 
				{
					if(count($withHistory[$previousHistoryPath]['fields']) > 0 && !in_array($firstKey, $withHistory[$previousHistoryPath]['fields']))
					{
						$withHistory[$previousHistoryPath]['fields'][] = $firstKey;
					}
				}

				if(count($withHistory[$currentHistoryPath]['fields']) > 0 && !in_array($secondKey, $withHistory[$currentHistoryPath]['fields']))
				{
					$withHistory[$currentHistoryPath]['fields'][] = $secondKey;
				}

				$previousModel = $model;
				
			}

			unset($previousModel);
		}

		//Apply the withHistory to using the laravel "with" function
		$withsArr = array();

		foreach($withHistory as $withHistoryKey => $withHistoryValue)
		{
			$withsArr[$withHistoryKey] = function($query) use ($withHistory, $withHistoryKey){

				//Reduce values to fieldname
				$fields = array_map(function($val) {
					$pos = strpos($val, '.');
					return $pos !== false ? substr($val, $pos+1) : $val;
				}, $withHistory[$withHistoryKey]['fields']);
				
				if(count($fields) > 0 && is_array($fields))
				{
					$query->select($fields);
				}

			};
		}


		$this->builder->with($withsArr);

		//Renew base fields
		if(count($fields) > 0)
		{
			$this->query->addSelect($fields);
		}
	}

	/**
	 * Parse the sort param and determine whether the sorting is ascending or descending.
	 * A descending sort has a leading "-". Apply it to the query.
	 * 
	 * @param  string 	$sortParam 
	 * @return void 
	 */
	protected function parseSort($sortParam)
	{
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

			$pair = array(preg_replace('/^-/', '', $sortElem), $direction);
			call_user_func_array(array($this->query, 'orderBy'), $pair);

		}
	}

	/**
	 * Parse the remaining filter params
	 * 
	 * @param  array 		$filterParams 
	 * 
	 * @return void
	 */
	protected function parseFilter($filterParams) 
	{
		$supportedPrefixesStr = array();

		$supportedPostfixes = array(
			'st' => '<', 
			'gt' => '>', 
			'min' => '>=', 
			'max' => '<=', 
			'lk' => 'LIKE',
			'not-lk' => 'NOT LIKE',
			'not' => '!=',
		);
		
		$supportedPrefixesStr = implode('|', $supportedPostfixes);
		$supportedPostfixesStr = implode('|', array_keys($supportedPostfixes));

		foreach($filterParams as $filterParamKey => $filterParamValue) 
		{
			$keyMatches = array();
			
			//Matches every parameter with an optional prefix and/or postfix
			//e.g. not-title-lk, title-lk, not-title, title 
			$keyRegex = '/^(?:('.$supportedPrefixesStr.')-)?(.*?)(?:-('.$supportedPostfixesStr.')|$)/';

			preg_match($keyRegex, $filterParamKey, $keyMatches);

			if(!isset($keyMatches[3]))
			{
				$comparator = '=';
			}
			else
			{
				$comparator = $supportedPostfixes[$keyMatches[3]];
			}

			$column = $keyMatches[2];

		 	$values = explode('|', $filterParamValue);

		 	if(count($values) > 1)
		 	{
				$this->query->where(function($query) use($column, $comparator, $values)
		        {
		            foreach($values as $value)
		            {
		            	
		            	//Link the filters with AND of there is a "not" and with OR if there's none
		            	if($comparator == '!=' || $comparator == 'NOT LIKE')
		            	{
		            		$query->where($column, $comparator, $value);
		            	}
		            	else 
		            	{
		            		$query->orWhere($column, $comparator, $value);
		            	}
		            }
		        });
			}
			else 
			{
				$value = $values[0];
				$this->query->where($column, $comparator, $value);
			}
		}
	}

	/**
	 * Parse the fulltext search parameter q
	 * 
	 * @param  string 	$qParam 
	 * @param  array 	$fullTextSearchColumns
	 * 
	 * @return void
	 */
	protected function parseFullTextSearch($qParam, $fullTextSearchColumns)
	{
		$keywords = explode(' ', $qParam);

		$this->query->where(function($query) use($fullTextSearchColumns, $keywords)
		{
			foreach($fullTextSearchColumns as $column)
			{
				foreach($keywords as $keyword)
		        {
		            $query->orWhere($column, 'LIKE', '%'.$keyword.'%');
		        }
			}
		});
	}

	/**
	 * Parse the meta parameter and prepare an array of meta provider objects.
	 * 
	 * @param  array 	$metaParam 
	 * @return void
	 */
	protected function parseMeta($metaParam)
	{
		$metaitems = explode(',',$metaParam);

		foreach($metaitems as $metaitem)
		{
			$metaitem = trim($metaitem);

			if($metaitem == 'total-count')
			{
				$this->meta[] = new CountMetaProvider($metaitem, $this->originalBuilder);
			}
			else if($metaitem == 'filter-count')
			{	
				$this->meta[] = new CountMetaProvider($metaitem, $this->builder);
			}
		}
	}

	/**
	 * Determine the type of the Eloquent relation
	 * 
	 * @param  Illuminate\Database\Eloquent\Relations\Relation $relation
	 * 
	 * @return string        
	 */
	protected function getRelationType($relation)
	{
		if($relation instanceof HasMany)
		{
			return 'HasMany';
		}

		if($relation instanceof BelongsTo)
		{
			return 'BelongsTo';
		}
	}
}
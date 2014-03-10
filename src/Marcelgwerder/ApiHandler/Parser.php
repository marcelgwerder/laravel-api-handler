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
	 * The mode for the response (count,default)
	 *
	 * @var string
	 */
	public $mode = 'default';

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
	 * Predefined functions
	 *
	 * @var array
	 */
	protected $functions = array('fields', 'sort', 'limit', 'offset','config', 'with', 'q');

	/**
	 * All functional params
	 */
	protected $functionalParams;

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
	public function __construct($builder, $params, $config)
	{

		$this->builder = $builder;
		$this->params = $params;
		$this->config = $config;

		$this->prefix = $this->config->get('api-handler::prefix');

		$isEloquentModel =  is_subclass_of($builder, '\Illuminate\Database\Eloquent\Model');
		$isEloquentRelation = is_subclass_of($builder, '\Illuminate\Database\Eloquent\Relations\Relation');

		$this->isEloquentBuilder = $builder instanceof \Illuminate\Database\Eloquent\Builder;
		$this->isQueryBuilder = $builder instanceof \Illuminate\Database\Query\Builder;

		if($this->isEloquentBuilder) 
		{
   			$this->query = $builder->getQuery();
		}
		else if($isEloquentRelation)
		{
			$this->builder = $builder->getQuery();
			$this->query = $builder->getBaseQuery();
			$this->isEloquentBuilder = true;
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
			if($sort = $this->getParam('sort'))
			{
				$this->parseSort($sort);
			}

			//Parse and apply offset using the laravel "offset" function
			if($offset = $this->getParam('offset'))
			{
				$offset = intval($offset);
				$this->query->offset($offset);
			}

			//Parse and apply limit using the laravel "limit" function
			if($limit = $this->getParam('limit'))
			{
				$limit = intval($limit);
				$this->query->limit($limit);
			}

			//Parse and apply the filters using the different laravel "where" functions
			//Every parameter that has not a predefined functionality gets parsed as a filter
			if($filterParams = $this->getFilterParams())
			{
				$this->parseFilter($filterParams);
			}

			//Parse an apply the fulltext search using the different laravel "where" functions
			//The fulltext search is only applied to the columns passed by $fullTextSearchColumns
			if($q = $this->getParam('q'))
			{
				$this->parseFulltextSearch($q, $fullTextSearchColumns);
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
		if($fields = $this->getParam('fields'))
		{
			$this->parseFields($fields);
		}

		//Parse and apply with elements using the Laravel "with" function 
		if(($with = $this->getParam('with')) && $this->isEloquentBuilder)
		{
			$this->parseWith($with);
		}		

		//Parse and apply the meta data
		if($config = $this->getParam('config'))
		{
			$this->parseConfig($config);
		}

		if($this->isEloquentBuilder)
		{
			//Attach the query builder object back to the eloquent builder object
			$this->builder->setQuery($this->query);
		}
	}

	/**
	 * Set the config object
	 * 
	 * @param 	mixed 	$config
	 */
	public function setConfigHandler($config)
	{
		$this->config = $config;
	}

	/**
	 * Get a parameter 
	 * 
	 * @param  string 			$param
	 * @return string|boolean
	 */
	protected function getParam($param)
	{
		if(isset($this->params[$this->prefix.$param]))
		{
			return $this->params[$this->prefix.$param];
		}

		return false;
	}

	/**
	 * Get the relevant filter parameters
	 * 
	 * @return array|boolean
	 */
	protected function getFilterParams()
	{
		$reserved = array_fill_keys($this->functions, true);
		$prefix = $this->prefix;

		$filterParams = array_diff_ukey($this->params, $reserved, function($a, $b) use ($prefix)
		{
			return ($a != $prefix.$b);
		});

		if(count($filterParams) > 0)
		{
			return $filterParams;
		}

		return false;
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

		if(count($fields) > 0)
		{
			$this->query->select($fields);
		}
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
	 * @param  array 	$filterParams 
	 * 
	 * @return void
	 */
	protected function parseFilter($filterParams) 
	{
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
		            	if($comparator == 'LIKE' || $comparator == 'NOT LIKE') $value = preg_replace('/(^\*|\*$)/', '%', $value);

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

				if($comparator == 'LIKE' || $comparator == 'NOT LIKE') $value = preg_replace('/(^\*|\*$)/', '%', $value);

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
	protected function parseConfig($configParam)
	{
		$configItems = explode(',',$configParam);

		foreach($configItems as $configItem)
		{
			$configItem = trim($configItem);

			$pos = strpos($configItem, '-');
			$cat = substr($configItem, 0, $pos);
			$option = substr($configItem, $pos+1);
			
			if($cat == 'mode')
			{
				if($option == 'count')
				{
					$this->mode = 'count';
				}
			}
			else if($cat == 'meta')
			{
				if($option == 'total-count')
				{
					$this->meta[] = new CountMetaProvider($option, $this->originalBuilder);
				}
				else if($option == 'filter-count')
				{
					$this->meta[] = new CountMetaProvider($option, $this->builder);
				}
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
<?php namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\Facades\Response;
use Illuminate\Database\QueryException;

class Result
{
	/**
	 * The builder Instance.
	 *
	 * @var mixed
	 */
	protected $builder;

	/**
	 * The original builder Instance.
	 *
	 * @var mixed
	 */
	protected $originalBuilder;
	
	/**
	 * An array of headers
	 *
	 * @var array
	 */
	protected $headers;


	public function __construct($type, $builder, $originalBuilder, $metaData)
	{
		$this->type = $type;
		$this->builder = $builder;
		$this->originalBuilder = $originalBuilder;
		$this->headers = $metaData;
	}

	/**
	 * Return a laravel response object including the correct status code and headers
	 * 
	 * @return Illuminate\Support\Facades\Response
	 */
	public function getResponse()
	{
		$result = $this->getResult();
		$headers = $this->getHeaders();

		return Response::json($result, 200, $headers);
	}

	/**
	 * Return the query builder including the results
	 * 
	 * @return Illuminate\Database\Query\Builder $result
	 */
	public function getResult()
	{
		try
		{
			if($this->type == 'single')
			{
				$result = $this->builder->first();
			}
			else if($this->type == 'multiple')
			{
				$result = $this->builder->get();
			}
		}
		catch(\BadMethodCallException $e)
		{
			$matches = array();
			$message = $e->getMessage();

			preg_match('/::(.+)\(\)$/', $message, $matches);

			$relation = $matches[1];

			throw new UndefinedRelationException($relation);
		}
		catch(QueryException $e)
		{
			$code = $e->getCode();
			$message = $e->getMessage();
			$matches = array();

			if($code == '42S22')
			{
				//Undefined column
				preg_match('/Unknown column \'([^\']+)/i', $message, $matches);

				$field = $matches[1];

				throw new UndefinedFieldException($field);
			}

			throw $e;
		}

		return $result;
	}

	/**
	 * Get the query bulder object
	 * 
	 * @return Illuminate\Database\Query\Builder 
	 */
	public function getBuilder()
	{
		return $this->queryBuilder;
	}

	/**
	 * Get the headers
	 * 
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}
}
<?php namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\Facades\Response;
use Illuminate\Database\QueryException;

class Result
{
	/**
	 * Parser instance.
	 *
	 * @var Marcelgwerder\ApiHandler\Parser
	 */
	protected $parser;

	public function __construct($parser)
	{
		$this->parser = $parser;
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
			if($this->parser->multiple)
			{
				$result = $this->parser->builder->get();
			}
			else
			{
				$result = $this->parser->builder->first();
			}
		}
		catch(\BadMethodCallException $e)
		{
			$matches = array();
			$message = $e->getMessage();

			preg_match('/::(.+)\(\)$/', $message, $matches);

			if(isset($matches[1]))
			{
				$relation = $matches[1];

				throw new UndefinedRelationException($relation);
			}

			throw $e;
		}
		catch(QueryException $e)
		{
			$code = $e->getCode();
			$message = $e->getMessage();
			$matches = array();

			if($code == '42S22')
			{
				preg_match('/Unknown column \'([^\']+)/i', $message, $matches);

				if(isset($matches[1]))
				{
					$field = $matches[1];

					throw new UndefinedFieldException($field);
				}

				throw $e;
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
		return $this->parser->builder;
	}

	/**
	 * Get the headers
	 * 
	 * @return array
	 */
	public function getHeaders()
	{
		$meta = $this->parser->meta;
		$headers = array();

		foreach($meta as $provider)
		{
			$headers[$provider->getTitle()] = $provider->get();
		}

		return $headers;
	}
}
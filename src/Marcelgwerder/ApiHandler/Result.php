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

	/**
	 * Create a new result
	 *
	 * @param  Marcelgwerder\ApiHandler\Parser $input
	 * @return void
	 */
	public function __construct(Parser $parser)
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
		$headers = $this->getHeaders();

		if($this->parser->mode == 'count')
		{
			return Response::json($headers, 200, $headers);
		}
		else {
			return Response::json($this->getResult(), 200, $headers);
		}
		
		
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
		} catch(Exception $e)
		{
			$this->handleException($e);	
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

		try
		{
			foreach($meta as $provider)
			{
				$headers[$provider->getTitle()] = $provider->get();
			}
		}
		catch(Exception $e)
		{
			$this->handleException($e);
		}


		return $headers;
	}

	/**
	 * Get an array of meta providers
	 * 
	 * @return array
	 */
	public function getMetaProviders() 
	{
		return $this->parser->meta;
	}

	/**
	 * Get the mode of the parser
	 * 
	 * @return string
	 */
	public function getMode()
	{
		return $this->parser->mode;
	}

	/**
	 * Handle an exception
	 * 
	 * @param  Exception $e
	 * @return void
	 */
	protected function handleException($e)
	{
		if($e instanceof \BadMethodCallException)
		{
			$matches = array();
			$message = $e->getMessage();

			preg_match('/::(.+)\(\)$/', $message, $matches);

			if(isset($matches[1]))
			{
				$relation = $matches[1];

				throw new UndefinedRelationException($relation);
			}
		}
		else if($e instanceof QueryException)
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
			}
		}

		throw $e;
	}
}
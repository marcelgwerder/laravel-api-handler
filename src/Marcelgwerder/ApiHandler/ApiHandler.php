<?php namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;


class ApiHandler 
{
	/**
	 * Return a new Result object for a single dataset
	 * 
	 * @param  mixed  							$queryBuilder  	Some kind of query builder instance 
	 * @param  array|integer 					$identification Identification of the dataset to work with
	 * @param  array|boolean 					$queryParams    The parameters used for parsing
	 * @return Marcelgwerder\ApiHandler\Result  				Result object that provides getter methods
	 */
	public function parseSingle($queryBuilder, $identification, $queryParams = false)
	{
		if($queryParams === false) $queryParams = $this->input->get();

		$parser = new Parser($queryBuilder, $queryParams, $this->config);
		$parser->parse($identification);

		return new Result($parser);
	}

	/**
	 * Return a new Result object for multiple datasets
	 * 
	 * @param  mixed  			$queryBuilder          Some kind of query builder instance 
	 * @param  array   			$fullTextSearchColumns Columns to search in fulltext search
	 * @param  array|boolean 	$queryParams           A list of query parameter
	 * @return Result                         
	 */
	public function parseMultiple($queryBuilder, $fullTextSearchColumns = array(), $queryParams = false)
	{
		if($queryParams === false) $queryParams = $this->input->get();

		$parser = new Parser($queryBuilder, $queryParams, $this->config);
		$parser->parse($fullTextSearchColumns, true);

		return new Result($parser);
	}

	/**
	 * Return a new "created" response object
	 * 
	 * @param  array|object   $object
	 * @return Response    
	 */
	public function created($object) 
	{
		return $this->response->json($object, 201);
	}

	/**
	 * Return a new "updated" response object
	 * 
	 * @param  array|object 	$object 
	 * @return Response    
	 */
	public function updated($object = null) 
	{
		if($object != null)  
		{
			return $this->response->json($object, 200);
		}
		else 
		{
			return $this->response->make(null, 204);
		}
	}

	/**
	 * Return a new "deleted" response object
	 * 
	 * @param  array|object 	$object
	 * @return Response
	 */
	public function deleted($object = null) {
		if($object != null)  
		{
			return $this->response->json($object, 200);
		}
		else 
		{
			return $this->response->make(null, 204);
		}
	}

	/**
	 * Set the input handler
	 * 
	 * @param Input $input
	 */
	public function setInputHandler($input)
	{
		$this->input = $input;
	}

	/**
	 * Set the config handler
	 * 
	 * @param Config $config
	 */
	public function setConfigHandler($config)
	{
		$this->config = $config;
	}

	/**
	 * Set the response handler
	 * 
	 * @param Response $response
	 */
	public function setResponseHandler($response)
	{
		$this->response = $response;
	}

	/**
	 * Set the current request
	 * 
	 * @param Request $request 
	 */
	public function setRequest($request) 
	{
		$this->request = $request;
	}
}
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
	 * @param  mixed  							$queryBuilder          Some kind of query builder instance 
	 * @param  array   							$fullTextSearchColumns Columns to search in fulltext search
	 * @param  array|boolean 					$queryParams           A list of query parameter
	 * @return Marcelgwerder\ApiHandler\Result                         
	 */
	public function parseMultiple($queryBuilder, $fullTextSearchColumns = array(), $queryParams = false)
	{
		if($queryParams === false) $queryParams = $this->input->get();

		$parser = new Parser($queryBuilder, $queryParams, $this->config);
		$parser->parse($fullTextSearchColumns, true);

		return new Result($parser);
	}

	/**
	 * Return an error response or throw an exception if debug mode is on
	 * and error is unknown
	 * 
	 * @param  Exception|integer 	$error   Exception object or an error code
	 * @param  string 				$display A message which can be shown to an enduser
	 * @param  array  				$headers HTTP headers
	 * @return Illuminate\Http\JsonResponse         		
	 */
	/*public function failed($error, $display = '', $headers = array())
	{
		if(is_numeric($error))
		{
			$error = new ApiHandlerException($error, $display);
		}
		else if(!($error instanceof ApiHandlerException) && is_subclass_of($error, 'Exception'))
		{
			$debug = $this->config->get('app.debug');

			if($debug == true)
			{
				throw $error;
			} 
			else
			{
				$errorConfig = $this->config->getPredefinedError('Unknown');
				$error = new ApiHandlerException($errorConfig['code'], $display);
			} 
		}

		if($error instanceof ApiHandlerException)
		{
			$response = Response::json(
				array(
					'code' 		=> $error->getCode(),
					'message' 	=> $error->getMessage(),
					'display' 	=> $error->getDisplay(),
				),
				$error->getHttpCode(),
				$headers
			);
		}
		else 
		{
			$response = false;
		}
		
		return $response;
	}*/

	public function setInputHandler($input)
	{
		$this->input = $input;
	}

	public function setConfigHandler($config)
	{
		$this->config = $config;
	}

	public function setResponseHandler($response)
	{
		$this->response = $response;
	}
}
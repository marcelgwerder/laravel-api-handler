<?php namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\Facades\Response;

class Result
{
	private $queryBuilder;
	private $queryBuilderOriginal;
	private $metaData;
	private $type;

	public function __construct($type, $queryBuilder, $queryBuilderOriginal, $metaData)
	{
		$this->type = $type;
		$this->queryBuilder = $queryBuilder;
		$this->queryBuilderOriginal = $queryBuilderOriginal;
		$this->metaData = $metaData;
	}

	public function getResponse()
	{

		if($this->type == 'single')
		{
			$result = $this->queryBuilder->first();
		}
		else if($this->type == 'multiple')
		{
			$result = $this->queryBuilder->get();
		}

		return Response::json(
			$this->queryBuilder->get(),
			200,
			$this->metaData
		);
	}

	public function getResult()
	{
		if($this->type == 'single')
		{
			$result = $this->queryBuilder->first();
		}
		else if($this->type == 'multiple')
		{
			$result = $this->queryBuilder->get();
		}

		return $result;
	}

	public function getBuilder()
	{
		return $this->queryBuilder;
	}

	public function getMeta()
	{
		return $this->metaData;
	}
}
<?php namespace Marcelgwerder\ApiHandler;

class ApiHandlerException extends \Exception
{
    protected $httpStatusCode = 500;

    public function __construct($message, $code = 0) 
    {
        parent::__construct($message, $code);
    }

    public function getHttpStatusCode()
    {
    	return $this->httpStatusCode;
    }
}
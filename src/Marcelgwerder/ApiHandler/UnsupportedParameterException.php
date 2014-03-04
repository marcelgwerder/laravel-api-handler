<?php namespace Marcelgwerder\ApiHandler

class UnsupportedParameterException extends Exception
{
    protected $httpStatusCode = 422;
    protected $code = 3;

    public function __construct($message, $code = 0) 
    {
        parent::__construct($message);
    }
}
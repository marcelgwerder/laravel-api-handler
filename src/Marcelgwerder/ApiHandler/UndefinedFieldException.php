<?php namespace Marcelgwerder\ApiHandler;

class UndefinedFieldException extends ApiHandlerException
{
    protected $httpStatusCode = 422;
    protected $code = 2;

    public function __construct($field) 
    {
    	$message = 'The field \''.$field.'\' is not available for this api resource';
        parent::__construct($message);
    }
}
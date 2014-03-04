<?php namespace Marcelgwerder\ApiHandler;

class UndefinedRelationException extends ApiHandlerException
{
	protected $httpStatusCode = 422;
	protected $code = 1;

    public function __construct($relation) 
    {

        $message = 'The relation \''.$relation.'\' is not defined for this api resource';

        parent::__construct($message);
    }
}
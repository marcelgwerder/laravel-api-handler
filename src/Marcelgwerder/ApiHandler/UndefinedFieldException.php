<?php namespace Marcelgwerder\ApiHandler;

class UndefinedFieldException extends ApiHandlerException
{
    public function __construct($field) 
    {
        $config = Config::getError('type', 'UndefinedField');
        $code = $config['code'];

        parent::__construct($code, '', array('field' => $field));
    }
}
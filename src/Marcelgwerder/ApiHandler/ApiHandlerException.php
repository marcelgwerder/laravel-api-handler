<?php namespace Marcelgwerder\ApiHandler;

class ApiHandlerException extends \Exception
{
	protected $type;
	protected $display;
	protected $httpCode;

    public function __construct($code, $display = '', $replacements = array()) 
    {
    	$config = Config::getError('code', $code);

    	$this->type = $config['type'];
    	$this->httpCode = $config['http_code'];  
    	$this->display = $display;

        $message = $config['message'];

        //Replace placeholders in $display and $message
        foreach ($replacements as $replacementKey => $replacement) 
        {
        	$message = str_replace(':'.$replacementKey, $replacement, $message);
        	$display = str_replace(':'.$replacementKey, $replacement, $display);
        }

        parent::__construct($message, $code);
    }

    public function getType()
    {
    	return $this->type;
    }

    public function getDisplay()
    {
    	return $this->display;
    }

    public function getHttpCode()
    {
    	return $this->httpCode;
    }
}
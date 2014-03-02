<?php namespace Marcelgwerder\ApiHandler;

class Config extends \Illuminate\Support\Facades\Config
{

	/**
	 * Get the config for a specific error according to a key/value pair
	 * 
	 * @param  string 			$key   
	 * @param  string|integer 	$value
	 * @return integer|boolean  
	 */
	public static function getError($key, $value)
	{
		$errors = Config::get('api-handler::errors');

		foreach($errors as $error)
		{
		    if($error[$key] === $value)
		    {
		    	return $error;
		    }
		}

		return false;
	}

	/**
	 * Get one of the predefined errors by its type 
	 * 
	 * @param  	string 	$type
	 * @return 	array 
	 */
	public static function getPredefinedError($type)
	{
		$predefinedErrors = Config::get('api-handler::predefined_errors');
		$code = $predefinedErrors[$type];

		return self::getError('code', $code);
	}
}
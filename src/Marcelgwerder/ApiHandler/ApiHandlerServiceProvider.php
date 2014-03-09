<?php namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Input;

class ApiHandlerServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('marcelgwerder/api-handler');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['ApiHandler'] = $this->app->share(function($app)
		{
			$apiHandler = new ApiHandler();

			$apiHandler->setInputHandler(new Input);
			$apiHandler->setResponseHandler(new Response);
			$apiHandler->setConfigHandler($app['config']);

			return $apiHandler;

		});

		$this->app->booting(function()
		{
		  $loader = \Illuminate\Foundation\AliasLoader::getInstance();
		  $loader->alias('ApiHandler', 'Marcelgwerder\ApiHandler\Facades\ApiHandler');
		  $loader->alias('ApiHandlerException', 'Marcelgwerder\ApiHandler\ApiHandlerException');
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
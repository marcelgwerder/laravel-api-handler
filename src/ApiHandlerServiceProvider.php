<?php namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\ServiceProvider;

class ApiHandlerServiceProvider extends ServiceProvider
{
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
        $this->mergeConfigFrom(
            __DIR__ . '/../config/apihandler.php', 'apihandler'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['ApiHandler'] = $this->app->share(function ($app) {
            return new ApiHandler;
        });
    }
}

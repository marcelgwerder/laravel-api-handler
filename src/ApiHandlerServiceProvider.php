<?php

namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\ServiceProvider;
use Marcelgwerder\ApiHandler\Parsers\FilterParser;

class ApiHandlerServiceProvider extends ServiceProvider
{
    protected $parsers = [
        FilterParser::class,
    ];

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/apihandler.php', 'apihandler'
        );

        $this->app->bind(ApiHandler::class, function ($app) {
            $apiHandler = new ApiHandler();

            foreach ($this->parsers as $parser) {
                $apiHandler->registerParser($parser);
            }

            return $apiHandler;
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/apihandler.php' => config_path('apihandler.php'),
        ]);
    }
}

<?php
namespace Marcelgwerder\ApiHandler;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use Marcelgwerder\ApiHandler\Parsers\PaginationParser;

class ApiHandlerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/apihandler.php', 'apihandler'
        );

        $this->app->bind(ApiHandler::class, function ($app) {
            $configRepository = new ConfigRepository(config('apihandler'));

            $apiHandler = new ApiHandler($configRepository);

            $parsers = $configRepository->get('parsers');

            foreach ($parsers as $parser) {
                $apiHandler->registerParser($parser);
            }

            // Register the pagination parser seperately since it is applied
            // in a slightly different way than the rest of the parsers.
            $apiHandler->registerParser(PaginationParser::class, true);

            $filters = $configRepository->get('filters');

            foreach ($filters as $suffix => $filter) {
                $apiHandler->registerFilter($suffix, new $filter);
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
            __DIR__ . '/../config/apihandler.php' => config_path('apihandler.php'),
        ]);
    }
}

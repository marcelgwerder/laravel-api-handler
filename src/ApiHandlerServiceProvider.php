<?php

namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\ServiceProvider;
use Illuminate\Config\Repository as ConfigRepository;

use Marcelgwerder\ApiHandler\Parsers\{
    SelectParser,
    FilterParser,
    SortParser,
    ExpansionParser,
    PaginationParser
};

use Marcelgwerder\ApiHandler\Filters\{
    EqualFilter,
    NotEqualFilter,
    LikeFilter,
    NotLikeFilter,
    InFilter,
    NotInFilter,
    MinFilter,
    MaxFilter,
    GreaterThanFilter,
    SmallerThanFilter
};

class ApiHandlerServiceProvider extends ServiceProvider
{
    protected $parsers = [
        SelectParser::class,
        FilterParser::class,
        SortParser::class,
        ExpansionParser::class,
        PaginationParser::class,
    ];

    protected $filters = [
        'default' => EqualFilter::class,
        'lk' => LikeFilter::class,
        'not-lk' => NotLikeFilter::class,
        'in' => InFilter::class,
        'not-in' => NotInFilter::class,
        'st' => SmallerThanFilter::class,
        'gt' => GreaterThanFilter::class,
        'min' => MinFilter::class,
        'max' => MaxFilter::class,
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

        $this->app->bind('apihandler', function ($app) {
            $configRepository = new ConfigRepository(config('apihandler'));
            
            $apiHandler = new ApiHandler($configRepository);

            foreach ($this->parsers as $parser) {
                $apiHandler->registerParser($parser);
            }

            foreach($this->filters as $suffix => $filter) {
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
            __DIR__.'/../config/apihandler.php' => config_path('apihandler.php'),
        ]);
    }
}

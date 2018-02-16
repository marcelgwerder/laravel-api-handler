<?php

namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\ServiceProvider;

use Marcelgwerder\ApiHandler\Parsers\{
    FilterParser,
    SortParser
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
        FilterParser::class,
        SortParser::class,
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
            $apiHandler = new ApiHandler();

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

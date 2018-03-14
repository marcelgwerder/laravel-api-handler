<?php

namespace Marcelgwerder\ApiHandler;

use Illuminate\Support\ServiceProvider;
use Illuminate\Config\Repository as ConfigRepository;

use Marcelgwerder\ApiHandler\Parsers\{
    SelectParser,
    FilterParser,
    SortParser,
    ExpansionParser,
    PaginationParser,
    SearchParser
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
    
    /**
     * The parsers used to parse and apply sepcific query parameter.
     * Note that the order of those parsers sometimes matters.
     * The search e.g. needs to come after the select and expansion parsers.
     * 
     * @var array
     */
    protected $parsers = [
        SelectParser::class,
        FilterParser::class,
        SortParser::class,
        ExpansionParser::class,
        SearchParser::class,
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

            // Register the pagination parser seperately since it is applied
            // in a slightly different way than the rest of the parsers.
            $apiHandler->registerParser(PaginationParser::class, true);

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

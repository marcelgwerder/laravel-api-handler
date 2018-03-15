<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CleanExpansions
    |--------------------------------------------------------------------------
    |
    | Whether expansions/relations that were internally added rather than 
    | by api request should be removed from the result. 
    | It usually makes sense since the developer did not request the expansion.
    |
     */

    'clean_expansions' => true,

    /*
    |--------------------------------------------------------------------------
    | CleanExpansions
    |--------------------------------------------------------------------------
    |
    | Wheter columns not explicitly selected should be removed from the result.
    |
     */
    'clean_selects' => true,

    /*
    |--------------------------------------------------------------------------
    | Search Driver
    |--------------------------------------------------------------------------
    |
    | Defines the driver used for fulltext search. The native driver requires
    | a fulltext index on the columns that should be searched. 
    |
     */

    'search_driver' => 'native',

    /*
    |--------------------------------------------------------------------------
    | Search Score Column
    |--------------------------------------------------------------------------
    |
    | Defines how the column is named that is returned 
    | for native fulltext search.
    |
     */
    'search_score_column' => 'search_score',

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
     */

    'default_page_size' => 30,

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
     */

    'max_page_size' => 500,

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
     */

    'default_filter' => Marcelgwerder\ApiHandler\Resources\Filters\EqualFilter::class,

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
     */

    'default_resource' => Marcelgwerder\ApiHandler\Resources\Json\Resource::class,

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
     */

    'default_resource_collection' => Marcelgwerder\ApiHandler\Resources\Json\ResourceCollection::class,

    /*
    |--------------------------------------------------------------------------
    | Searchable
    |--------------------------------------------------------------------------
    |
    | This array contains the searchable columns that will be allowed for every
    | request unless overridden by the model or endpoint config. 
    | Usually, this property stays empty in the global config.
    |
     */
    'searchable' => [],

    /*
    |--------------------------------------------------------------------------
    | Filterable
    |--------------------------------------------------------------------------
    |
    | This array contains the filterable columns that will be allowed for every
    | request unless overridden by the model or endpoint config. 
    | Usually, this property stays empty or contains a single '*' wildcard entry.
    |
     */
    'filterable' => [],

    /*
    |--------------------------------------------------------------------------
    | Sortable
    |--------------------------------------------------------------------------
    |
    | This array contains the sortable columns that will be allowed for every
    | request unless overridden by the model or endpoint config. 
    | Usually, this property stays empty or contains a single '*' wildcard entry.
    |
     */
    'sortable' => [],

    /*
    |--------------------------------------------------------------------------
    | Expandable
    |--------------------------------------------------------------------------
    |
    | This array contains the expandable columns that will be allowed for every
    | request unless overridden by the model or endpoint config. 
    | Usually, this property stays empty or contains a single '*' wildcard entry.
    |
     */
    'expandable' => [],

    /*
    |--------------------------------------------------------------------------
    | Selectable
    |--------------------------------------------------------------------------
    |
    | This array contains the selectable columns that will be allowed for every
    | request unless overridden by the model or endpoint config. 
    | Usually, this property stays empty or contains a single '*' wildcard entry.
    |
     */
    'selectable' => ['*'],


    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    |
    | This array contains all filters that can be used by the filter parser.
    |
     */
    'filters' => [
        'default' => Marcelgwerder\ApiHandler\Filters\EqualFilter::class,
        'lk' => Marcelgwerder\ApiHandler\Filters\LikeFilter::class,
        'not-lk' => Marcelgwerder\ApiHandler\Filters\NotLikeFilter::class,
        'in' => Marcelgwerder\ApiHandler\Filters\InFilter::class,
        'not-in' => Marcelgwerder\ApiHandler\Filters\NotInFilter::class,
        'st' => Marcelgwerder\ApiHandler\Filters\SmallerThanFilter::class,
        'gt' => Marcelgwerder\ApiHandler\Filters\GreaterThanFilter::class,
        'min' => Marcelgwerder\ApiHandler\Filters\MinFilter::class,
        'max' => Marcelgwerder\ApiHandler\Filters\MaxFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Parsers
    |--------------------------------------------------------------------------
    |
    | This array contains all parsers used to parse the query
    | parameters and apply them to the query builder.
    |
     */
    'parsers' => [
        Marcelgwerder\ApiHandler\Parsers\SelectParser::class,
        Marcelgwerder\ApiHandler\Parsers\FilterParser::class,
        Marcelgwerder\ApiHandler\Parsers\SortParser::class,
        Marcelgwerder\ApiHandler\Parsers\ExpansionParser::class,
        Marcelgwerder\ApiHandler\Parsers\PaginationParser::class,
        Marcelgwerder\ApiHandler\Parsers\SearchParser::class,
    ],
];

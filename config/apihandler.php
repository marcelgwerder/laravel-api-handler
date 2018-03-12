<?php

use Marcelgwerder\ApiHandler\Filters\EqualFilter;
use Marcelgwerder\ApiHandler\Resources\Json\Resource;
use Marcelgwerder\ApiHandler\Resources\Json\ResourceCollection;

return [

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

    'fulltext_search' => 'native',

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

    'default_filter' => EqualFilter::class,

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

    'default_limit' => 500,

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

    'default_resource' => Resource::class,

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

    'default_resource_collection' => ResourceCollection::class,

    /*
    |--------------------------------------------------------------------------
    | Searchable
    |--------------------------------------------------------------------------
    |
    | This array contains the searchable columns that will be allowed for every
    | request unless overridden by the model or endpoint config. 
    | Usually, this property stays empty or contains a single '*' wildcard entry.
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
    'selectable' => [],

];

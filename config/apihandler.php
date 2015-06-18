<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Query Parameter Prefix
    |--------------------------------------------------------------------------
    |
    | Defines the prefix used for the predefined query parameters such as:
    | fields, sort or with
    |
     */

    'prefix' => '_',

    /*
    |--------------------------------------------------------------------------
    | Envelope
    |--------------------------------------------------------------------------
    |
    | Define whether to use an envelope for meta data or not. By default the
    | meta data will be in the response header not in the body.
    |
     */

    'envelope' => false,

    /*
    |--------------------------------------------------------------------------
    | Fulltext Search
    |--------------------------------------------------------------------------
    |
    | The type of fulltext search, either "default" or "native".
    | Native fulltext search for InnoDB tables is only supported by MySQL versions >= 5.6.
    |
     */

    'fulltext' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Fulltext Search Score Column
    |--------------------------------------------------------------------------
    |
    | The name of the column containing the fulltext search score in native
    | fulltext search mode.
    |
     */

    'fulltext_score_column' => '_score',

    /*
    |--------------------------------------------------------------------------
    | Errors
    |--------------------------------------------------------------------------
    |
    | These arrays define the default error messages and the corresponding http
    | status codes.
    |
     */

    'errors' => [
        'ResourceNotFound' => ['http_code' => 404, 'message' => 'The requested resource could not be found but may be available again in the future.'],
        'InternalError' => 	['http_code' => 500, 'message' => 'Internal server error'],
        'Unauthorized' => ['http_code' => 401, 'message' => 'Authentication is required and has failed or has not yet been provided'],
        'Forbidden' => ['http_code' => 403, 'message' => 'You don\'t have enough permissions to access this resource'],
        'ToManyRequests' => ['http_code' => 429, 'message' => 'You have sent too many requests in a specific timespan'],
        'InvalidInput' => ['http_code' => 400, 'message' => 'The submited data is not valid'],
        'InvalidQueryParameter' => ['http_code' => 400, 'message' => 'Invalid parameter'],
        'UnknownResourceField' => ['http_code' => 400, 'message' => 'Unknown field ":field"'],
        'UnknownResourceRelation' => ['http_code' => 400, 'message' => 'Unknown relation ":relation"'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Predefined Errors
    |--------------------------------------------------------------------------
    |
    | Link the errors the api handler uses internaly with the the respective
    | error above.
    |
     */

    'internal_errors' => [
        'UnknownResourceField' => 'UnknownResourceField',
        'UnknownResourceRelation' => 'UnknownResourceRelation',
        'UnsupportedQueryParameter' => 'UnsupportedQueryParameter',
        'InvalidQueryParameter' => 'InvalidQueryParameter',
    ],
];

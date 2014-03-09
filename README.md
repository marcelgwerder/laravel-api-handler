##Laravel API Handler##
[![Build Status](https://travis-ci.org/marcelgwerder/laravel-api-handler.png?branch=master)](https://travis-ci.org/marcelgwerder/laravel-api-handler)

**Still in initial development!**

This is going to be a helper package which provides functionality for url parsing and response handling on a Laravel REST-API. 

###URL Parsing###

There are two kind of api resources supported.

####A Single Object####

If you handle a GET request on a resource representing a single object like for example `/api/books/1`, use the `parseSingle` method.

**parseSingle($queryBuilder, $identification, [$queryParams]):**
* **$queryBuilder**: Any object that inherits from the Laravel Query Builder or an Eloquent model.
* **$identification**: An integer used in the `id` column or an array with a column/value pair (`array('isbn', '1234')`) used as a unique identifier of the object.
* **$queryParams**: An array containing the query parameters. If not defined, the original GET parameters are used.

```php
ApiHandler::parseSingle($book, 1);
```

####A Collection of Objects####

If you handle a GET request on a resource representing multiple objects like for example `/api/books`, use the `parseMultiple` method.

**parseMultiple($queryBuilder, $fullTextSearchColumns, [$queryParams]):**
* **$queryBuilder**: Any object that inherits from the Laravel Query Builder or an Eloquent model.
* **$fullTextSearchColumns**: An array which defines the columns used for full text search.
* **$queryParams**: An array containing the query parameters. If not defined, the original GET parameters are used.

```php
ApiHandler::parseMultiple($book, array('title', 'isbn', 'description'));
```

####Result####

Both `parseSingle` and `parseMultiple` return a `Result` object with the following methods available:

**getBuilder():**
Returns the original `$queryBuilder` with all the functions applied to it.

**getResult():**
Returns the result object returned by Laravel's `get()` or `first()` functions.

**getResponse():**
Returns a Laravel `Response` object including body, headers and HTTP status code.

**getHeaders():**
Returns an array of prepared headers.

####Filtering####

#####Suffixes#####
Suffix        | Operator      | Meaning
------------- | ------------- | -------------
-lk           | LIKE          | Same as the SQL `LIKE` opearator
-not-lk       | NOT LIKE      | Same as the SQL `NOT LIKE` operator
-min          | >=            | Greater than or equal to
-max          | <=            | Smaller than or equal to
-st           | <             | Smaller than
-gt           | >             | Greater than
-not          | !=            | Not equal to

####Sorting####

####Full Text Search####

####Limit The Result Set###

####Include Related Models####

####Include Meta Information####

###Response Handling###

####Error Response####

####Success Response####

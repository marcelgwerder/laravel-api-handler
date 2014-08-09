##Laravel API Handler##
[![Build Status](https://travis-ci.org/marcelgwerder/laravel-api-handler.png?branch=master)](https://travis-ci.org/marcelgwerder/laravel-api-handler) [![Latest Stable Version](https://poser.pugx.org/marcelgwerder/laravel-api-handler/v/stable.png)](https://packagist.org/packages/marcelgwerder/laravel-api-handler) [![Total Downloads](https://poser.pugx.org/marcelgwerder/laravel-api-handler/downloads.png)](https://packagist.org/packages/marcelgwerder/laravel-api-handler) [![License](https://poser.pugx.org/marcelgwerder/laravel-api-handler/license.png)](https://packagist.org/packages/marcelgwerder/laravel-api-handler)

This helper package provides functionality for parsing the url of a rest api request.

###Installation###

Install the package through composer by adding it to your `composer.json` file:

```
"require": {
    "marcelgwerder/laravel-api-handler": "0.3.*"
}
```
Then run `composer update`. Once composer finished add the service provider to the `providers` array in `app/config/app.php`:
```
'Marcelgwerder\ApiHandler\ApiHandlerServiceProvider'
```
That's it!

###URL Parsing###

Url parsing currently supports:
* Limit the fields
* Filtering
* Limited full text search
* Sorting
* Define limit and offset
* Append related models
* Append meta information (counts)


There are two kind of api resources supported, a single object and a collection of objects.

####A Single Object####

If you handle a GET request on a resource representing a single object like for example `/api/books/1`, use the `parseSingle` method.

**parseSingle($queryBuilder, $identification, [$queryParams]):**
* **$queryBuilder**: Query builder object, Eloquent model or Eloquent relation
* **$identification**: An integer used in the `id` column or an array with a column/value pair (`array('isbn', '1234')`) used as a unique identifier of the object.
* **$queryParams**: An array containing the query parameters. If not defined, the original GET parameters are used.

```php
ApiHandler::parseSingle($book, 1);
```

####A Collection of Objects####

If you handle a GET request on a resource representing multiple objects like for example `/api/books`, use the `parseMultiple` method.

**parseMultiple($queryBuilder, $fullTextSearchColumns, [$queryParams]):**
* **$queryBuilder**: Query builder object, Eloquent model or Eloquent relation
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

**getMetaProviders():**
Returns an array of meta provider object. Each of these objects provide a specific type of meta data through its `get()` method.

####Filtering####
Every query parameter, except the predefined functions `_fields`, `_with`, `_sort`, `_limit`, `_offset`, `_config` and `_q`, is interpreted as a filter. Be sure to remove additional parameters not meant for filtering before passing them to `parseMultiple`.

```
/api/books?title=The Lord of the Rings
```
All the filters are combined with an `AND` oparator.
```
/api/books?title-lk=The Lord*&created_at-min=2014-03-14 12:55:02
```
The above example would result in the following SQL where:
```sql
WHERE `title` LIKE "The Lord%" AND `created_at` >= "2014-03-14 12:55:02"
```
Its also possible to use multiple values for one filter. Multiple values are seperated by a pipe `|`.
Multiple values are combined with `OR` except when there is a `-not` suffix, then they are combined with `AND`.
For example all the books with the id 5 or 6:
```
/api/books?id=5|6
```
Or all the books except the ones with id 5 or 6:
```
/api/books?id-not=5|6
```


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
Two ways of sorting, ascending and descending. Every column which should be sorted descending allways starts with a `-`.
```
/api/books?_sort=-title,created_at
```

####Full Text Search####
The api handler url parsing function also supports a limited full text search. A given text is split into keywords which then are searched in the database. Whenever one of the keyword exists, the corresponding row is included in the result set.

```
/api/books?_q=The Lord of the Rings
```
The above example returns every row that contains one of the keywords `The`, `Lord`, `of`, `the`, `Rings` in one of its columns. The columns to consider in full text search are passed to `parseMultiple`.

####Limit The Result Set###
To define the maximum amount of datasets in the result, use `_limit`.
```
/api/books?_limit=50
```
To define the offset of the datasets in the result, use `_offset`.
```
/api/books?_offset=20&_limit=50
```
Be aware that in order to use `offset` you alwas have to specify a `limit` too. MySQL throws an error for offset definition without a limit.

####Include Related Models####
The api handler also supports Eloquent relationships. So if you want to get all the books with their authors, just add the authors to the `_with` parameter.
```
/api/books?_with=author
```
Relationships, can also be nested:
```
/api/books?_with=author.awards
```

***Important information:*** Whenever you limit the fields with `_fields` in combination with `_with`. Under the hood the fields are extended with the primary/foreign keys of the relation. Eloquent needs the linking keys to get related models.

####Include Meta Information####
It's possible to add additional information to a response. There are currently two types of counts which can be added to the response headers.

The `total-count` which represents the count of all elements of a resource or to be more specific, the count on the originally passed query builder instance.
The `filter-count` which additionally takes filters into account. They can for example be useful to implement pagination.

```
/api/books?id-gt=5&_config=meta-total-count,meta-filter-count
```
All meta fields are provided in the response header. There is no such "envelope" thing in the response body.
The following custom headers are used:

Config            | Header
----------------- | -------------
meta-total-count  | Meta-Total-Count
meta-filter-count | Meta-Filter-Count

####Use an envelope for the response
By default meta data is included in the response header. If you want to have everything togheter in the response body you can request a so called "envelope"
either by including `response-envelope` in the `_config` parameter or by overriding the default `config.php` of the package.

The envelope has the following structure:

```json
{
  "meta": {
    ...
  },
  "data": [
    ...
  ]
}
```

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
Every query parameter except the predefined functions `_fields`, `_with`, `_sort`, `_limit`, `_offset`, `_config` and `_q` is interpreted as a filter. Be sure to remove additional parameters not meant for filtering before passing them to `parseMultiple`.

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
Multiple values are combined with `OR` except when there is a `-not` prefix, then they are combined with `AND`.
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
/api/books?_offset=20
```

####Include Related Models####
The api handler also supports Eloquent relationships. So if you want to get all the books with their authors, just add the authors to the `_with` parameter.
```
/api/books?_with=author
```
Relationships, can also be nested:
```
/api/books?_with=author.awards
```

***Important information:*** Whenever you limit the fields with `_fields` in combination with `_with`. Under the hood the fields are extended with the primary/foreign keys of the relation. Eloquent needs the linking keys to get related models. This unfortunately right now only works with primary keys named `id`.

####Include Meta Information####

###Response Handling###

####Error Response####

####Success Response####

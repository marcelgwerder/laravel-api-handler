## Laravel API Handler


This helper package provides functionality for parsing the URL of a REST-API request.

### Installation

Install the package through composer by running 
```
composer require marcelgwerder/laravel-api-handler:dev-next
```

Once composer finished add the service provider to the `providers` array in `app/config/app.php`:
```
Marcelgwerder\ApiHandler\ApiHandlerServiceProvider::class,
```
Now import the `ApiHandler` facade into your classes:
```php
use Marcelgwerder\ApiHandler\Facades\ApiHandler;
```
Or set an alias in `app.php`:
```
'ApiHandler' => Marcelgwerder\ApiHandler\Facades\ApiHandler::class,
```
That's it!

### Client-Side Usage

#### Select Specific Columns

If you only want to fetch very specific data, you can define which columns are fetched by using the  `select` query parameter:

```
/api/posts?select=id,title
```
The above request will only return the id and title of the post. 

A special case when using search:
- When using native search, you can also specifically select the search score column by adding the column name configured in the `search_score_column` config property which is `search_score` by default.

Two things to consider when having `clean_selects` set to `false` or when using a custom resource:
- When you use `sort` with the `search_score_column`, the column will also be in the response, even if not specifically selected.
- When you use `expand`, the keys needed to match the relations will also be in the response, even if not specifically selected.

#### Filter the Result

To filter to result, the `filter` query parameter can be used. The suffix defines which filter to use. If no suffix is given, the the filter defined in `default_filter` is used, by default it's a simple equality filter (`=`). The column to filter by is defined in the brackets.

```
/api/posts?filter-gt[id]=5

```

The below filters are included by default:

Suffix        | Operator      | Meaning
------------- | ------------- | -------------
-lk           | LIKE          | Same as the SQL `LIKE` operator
-not-lk       | NOT LIKE      | Same as the SQL `NOT LIKE` operator
-in           | IN            | Same as the SQL `IN` operator
-not-in       | NOT IN        | Same as the SQL `NOT IN` operator
-min          | >=            | Greater than or equal to
-max          | <=            | Smaller than or equal to
-st           | <             | Smaller than
-gt           | >             | Greater than
-not          | !=            | Not equal to
-nl           | IS NULL       | Is null
-not-nl       | IS NOT NULL   | Is not null

#### Sort the Result

The `sort` query parameter is used to sort the result.
By default, the order is *ascending*, prepending a dash (`-`) to the column will make it *descending*:

```
/api/posts?sort=-created_at,title
```

A special case when using search:
- When using native search, you can also specifically sort by the search score column, using the column name configured in the `search_score_column` config property, which is `search_score` by default.


#### Paginate the Result

The Api Handler uses Laravels core pagination feature to paginate results. The `page` query parameter defines the page. Additionally, the page size can be set using the `pageSize` query parameter:

```
/api/posts?page=2&pageSize=10
```
- The `pageSize` parameter is optional, by default the value in `default_page_size` is used.
- Submitting a `pageSize` with a value greater than the `default_limit` will throw an exception.

The default resource will automatically include the necessary pagination information in the result:
```json
{
    "data": [
        ...
    ],
    "links": {
        "first": "https://laravel/api/posts?pageSize=10&page=1",
        "last": "https://laravel/api/posts?pageSize=10&page=4",
        "prev": "https://laravel/api/posts?pageSize=10&page=1",
        "next": "https://laravel/api/posts?pageSize=10&page=3"
    },
    "meta": {
        "current_page": 2,
        "from": 11,
        "last_page": 4,
        "path": "https://laravel/api/posts",
        "per_page": 10,
        "to": 20,
        "total": 40
    }
}
```

#### Search the Database
To search the database, the `search` query parameter is used. Currently this will automatically use MySQL's native fulltext search. For this to work, the correct fulltext index has to be added to the columns defined in `searchable`:

```
/api/posts?search=Lorem Ipsum
```
Filters and the search are combined by `AND`.

#### Expand the Results with Related Entities

Related entities can be requested by using the `expand` query parameter. To be able to expand a relation, it has to be in the `expandable` array and the relation method needs to typehint the return value as `Illuminate\Database\Eloquent\Relations\Relation`. This to ensure the api handler can't call any other methods on the model, which would be a security issue. 

```
/api/posts?expand=comments
```

Additionally, it is also possible to select specific columns from a relation by listing them in the brackets:

```
/api/posts?expand=comments[message,created_at]
```
**Note:** It is not possible to select specific fields on polymorphic relations. 

### Basic Implementation

#### Single Item Endpoint

Example path:
```
/api/posts/1
```

Endpoint code:
```php
return ApiHandler::from(Post::whereKey($id))
    ->asResource();
```

#### Multiple Items Endpoint

Example path:
```
/api/posts
```

Endpoint code:
```php
return ApiHandler::from(Post::class)
   ->asResourceCollection();
```

### Advanced Implemntation

#### Change Endpoint Configuration

Any of the configuration properties can be configured through calling the camelCase method when defining an endpoint. There are two special behaviors for convenience:
- Multiple parameters are converted to a single array
- Calls without parameters are converted to `true`.

```php
return ApiHandler::from(Post::class)
   ->searchDriver('native')     // 'search_driver' => 'native'
   ->selectable('id', 'title')  // 'selectable' => ['id', 'title']
   ->cleanExpansions()          // 'clean_expansions' => true
   ->asResourceCollection();
```

#### Register Endpoint Scopes

To be able to have a single chain of method calls in your endpoint definition, you can register Laravel scopes (both closures and class instances) directly in the method chain:

```php
return ApiHandler::from(Post::class)
   ->registerScope(new ExampleScope())
   ->registerScope(function($query) {
       $query->whereIn('type', ['video', 'embed']);
   })
   ->asResourceCollection();
```

#### Return Custom Resources

You can override the resource collections and resource classes used by passing your own class as an argument to `asResource` or `asResourceCollection` respectively:
```php
return ApiHandler::from(Post::class)
   ->asResource(Foo\Bar\Resources\PostResource::class);
```
This an alternative to just overriding the `default_resource` or `default_resource_collection` properties of the configuration by calling the respective camelCase methods.


### Extend Core Functionality

#### Create and Register Custom Filters

Filters are used by the filter parser and can be added and removed at will. Filters consist of an implementation and a key, which is the suffix by which the filter is referenced in the query parameter, e.g. `?filter-custom=Lorem Ipsum` where `custom` is the key.

There are two ways to register a filter: By adding it to the configuration or registering it ad-hoc for a speficic endpoint. When using ad-hoc registration, you can either register a class or use a closure. When adding filters to the configuration, you have to use a class.

When using a filter class, the class must implement the `Marcelgwerder\ApiHandler\Contracts\Filter` interface.

**Note:** Registering a filter with the key of an already registered filter will override the existing filter.

**Ad-hoc registration with closure:**
```php 
return ApiHandler::from(Post::class)
    ->registerFilter('custom', function($builder, $value, $property = null, $relation = null) {
        $builder->where('published_until', '<', $value);
    })
    ->asResourceCollection();
```

**Ad-hoc registration with class:**
```php 
return ApiHandler::from(Post::class)
    ->registerFilter('custom', Foo\Bar\Filters\CustomFilter::class)
    ->asResourceCollection();
```

**Config registration with class:**
```php

'filters' => [
    // ...
    'custom' => Foo\Bar\Filters\CustomFilter::class,
]
```

#### Create and Register Custom Parsers

TBD


### Configuration

<table>
    <thead>
        <tr>
            <th>Key</th>
            <th>Type</th>
            <th>Options</th>
            <th>Default</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>clean_expansions</td>
            <td>boolean</td>
            <td>true, false</td>
            <td>true</td>
            <td>Whether expansions/relations that were internally added rather than by api request should be removed from the result. It usually makes sense since the developer did not request the expansion.</td>
        </tr>
        <tr>
            <td>clean_selects</td>
            <td>boolean</td>
            <td>true, false</td>
            <td>true</td>
            <td>Wheter columns not explicitly selected should be removed from the result.</td>
        </tr>
        <tr>
            <td>search_driver</td>
            <td>string</td>
            <td>native</td>
            <td>native</td>
            <td>Defines the driver used for fulltext search. The native driver requires a fulltext index on the columns that should be searched.</td>
        </tr>
        <tr>
            <td>search_score_column</td>
            <td>string</td>
            <td></td>
            <td>search_score</td>
            <td>Defines how the column is named that is returned for native fulltext search.</td>
        </tr>
        <tr>
            <td>default_page_size</td>
            <td>int</td>
            <td></td>
            <td>30</td>
            <td>The default page size used when no other page size is set.</td>
        </tr>
        <tr>
            <td>max_page_size</td>
            <td>int</td>
            <td></td>
            <td>500</td>
            <td>The max possible value, the page size can be set to by the client.</td>
        </tr>
        <tr>
            <td>default_filter</td>
            <td>string</td>
            <td></td>
            <td><i>core equal filter</i></td>
            <td>The filter used when there is no suffix specified.</td>
        </tr>
        <tr>
            <td>default_resource</td>
            <td>string</td>
            <td></td>
            <td><i>core resource</i></td>
            <td>The default resource used for the response.</td>
        </tr>
        <tr>
            <td>default_resource_collection</td>
            <td>string</td>
            <td></td>
            <td><i>core resource collection</i></td>
            <td>The default resource collection used for the response.</td>
        </tr>
        <tr>
            <td>searchable</td>
            <td>array</td>
            <td></td>
            <td>[]</td>
            <td>The columns that will be allowed for every request unless overridden by the model or endpoint config. Usually, this property stays empty in the global config.</td>
        </tr>
        <tr>
            <td>filterable</td>
            <td>array</td>
            <td></td>
            <td>[]</td>
            <td>The columns that will be allowed for every request unless overridden by the model or endpoint config. Usually, this property stays empty in the global config.</td>
        </tr>
        <tr>
            <td>sortable</td>
            <td>array</td>
            <td></td>
            <td>[]</td>
            <td>The sortable columns that will be allowed for every request unless overridden by the model or endpoint config. Usually, this property stays empty or contains a single '*' wildcard entry.</td>
        </tr>
        <tr>
            <td>selectable</td>
            <td>array</td>
            <td></td>
            <td>['*']</td>
            <td>The selectable columns that will be allowed for every request unless overridden by the model or endpoint config. Usually, this property stays empty or contains a single '*' wildcard entry.</td>
        </tr>
        <tr>
            <td>expandable</td>
            <td>array</td>
            <td></td>
            <td>[]</td>
            <td>The expandable relations that will be allowed for every request unless overridden by the model or endpoint config. Usually, this property stays empty or contains a single '*' wildcard entry.</td>
        </tr>
        <tr>
            <td>filters</td>
            <td>array</td>
            <td></td>
            <td><i>core filters</i></td>
            <td>The registered filters.</td>
        </tr>
        <tr>
            <td>parsers</td>
            <td>array</td>
            <td></td>
            <td><i>core parsers</i></td>
            <td>The registered filters.</td>
        </tr>
    </tbody>
</table>





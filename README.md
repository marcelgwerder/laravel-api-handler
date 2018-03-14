## Laravel API Handler
[![Build Status](https://travis-ci.org/marcelgwerder/laravel-api-handler.png?branch=master)](https://travis-ci.org/marcelgwerder/laravel-api-handler) [![Latest Stable Version](https://poser.pugx.org/marcelgwerder/laravel-api-handler/v/stable.png)](https://packagist.org/packages/marcelgwerder/laravel-api-handler) [![Total Downloads](https://poser.pugx.org/marcelgwerder/laravel-api-handler/downloads.png)](https://packagist.org/packages/marcelgwerder/laravel-api-handler) [![License](https://poser.pugx.org/marcelgwerder/laravel-api-handler/license.png)](https://packagist.org/packages/marcelgwerder/laravel-api-handler)

This helper package provides functionality for parsing the URL of a REST-API request.

### Installation

Install the package through composer by running 
```
composer require marcelgwerder/laravel-api-handler
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

### Usage

TBD


[![Total Downloads](https://poser.pugx.org/DarkaOnLine/L5-Swagger/downloads.svg)](https://packagist.org/packages/DarkaOnLine/L5-Swagger)
[![Build Status](https://travis-ci.org/DarkaOnLine/L5-Swagger.svg?branch=master)](https://travis-ci.org/DarkaOnLine/L5-Swagger)
[![StyleCI](https://styleci.io/repos/32315619/shield)](https://styleci.io/repos/32315619)

L5 Swagger
==========

Swagger 2.0 for Laravel >=5.1

This package is a wrapper of [Swagger-php](https://github.com/zircote/swagger-php) and [swagger-ui](https://github.com/swagger-api/swagger-ui) adapted to work with Laravel 5.

Installation
============

For Swagger 2.0
```php
    composer require "darkaonline/l5-swagger:~3.0"
```

For Swagger 1.0
```php
    composer require "darkaonline/l5-swagger:~0.1"
```

- Open your `AppServiceProvider` (located in `app/Providers`) and add this line in `register` function
```php
    $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
```
the final function should similar to this:
```php
    public function register()
    {
        //Register Swagger Provider
        $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
    }
```

Changes in 3.0
============
- More accurate naming and structured config
- Swagger-ui is now pulled from bower and is no longer included in package files. Bower will be installed in your vendor path if you don't have it

Migrate from 2.0 to 3.0
============
- Replace `$this->app->register('\Darkaonline\L5Swagger\L5SwaggerServiceProvider');` with `$this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);` in your `AppServiceProvider`
- Run `l5-swagger:publish-config` to publish new config and make your changes if needed
- Remove `public/vendor/l5-swagger` directory
- Remove `resources/views/vendor/l5-swagger` directory
- Run `l5-swagger:publish-assets` to publish new swagger-ui assets
- Run `l5-swagger:publish-views` to publish new views

Configuration
============
- Run `l5-swagger:publish` to publish everything
- Run `l5-swagger:publish-config` to publish configs (`config/l5-swagger.php`)
- Run `l5-swagger:publish-assets` to publish swagger-ui to your public folder (`public/vendor/l5-swagger`)
- Run `l5-swagger:publish-views` to publish views (`resources/views/vendor/l5-swagger`)
- Run `l5-swagger:generate` to generate docs or set `generate_always` param to `true` in your config or .env file 

Swagger-php
======================
The actual Swagger spec is beyond the scope of this package. All L5-Swagger does is package up swagger-php and swagger-ui in a Laravel-friendly fashion, and tries to make it easy to serve. For info on how to use swagger-php [look here](http://zircote.com/swagger-php/). For good examples of swagger-php in action [look here](https://github.com/zircote/swagger-php/tree/master/Examples/petstore.swagger.io).

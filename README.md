[![Total Downloads](https://poser.pugx.org/DarkaOnLine/L5-Swagger/downloads.svg)](https://packagist.org/packages/DarkaOnLine/L5-Swagger)
[![Build Status](https://travis-ci.org/DarkaOnLine/L5-Swagger.svg?branch=master)](https://travis-ci.org/DarkaOnLine/L5-Swagger)
[![Coverage Status](https://coveralls.io/repos/github/DarkaOnLine/L5-Swagger/badge.svg?branch=master)](https://coveralls.io/github/DarkaOnLine/L5-Swagger?branch=master)
[![Code Climate](https://codeclimate.com/github/DarkaOnLine/L5-Swagger/badges/gpa.svg)](https://codeclimate.com/github/DarkaOnLine/L5-Swagger)
[![StyleCI](https://styleci.io/repos/32315619/shield)](https://styleci.io/repos/32315619)

L5 Swagger
==========

Swagger 2.0 for Laravel >=5.1

This package is a wrapper of [Swagger-php](https://github.com/zircote/swagger-php) and [swagger-ui](https://github.com/swagger-api/swagger-ui) adapted to work with Laravel 5.

Installation
============

For Swagger 2.0

 Laravel  | Swagger UI| OpenAPI Spec compatibility | L5-Swagger
:---------|:----------|:---------------------------|:----------
 5.1.x    | 2.2       | 1.1, 1.2, 2.0              | ```php composer require "darkaonline/l5-swagger:~3.0" ```
 5.2.x    | 2.2       | 1.1, 1.2, 2.0              | ```php composer require "darkaonline/l5-swagger:~3.0" ```
 5.3.x    | 2.2       | 1.1, 1.2, 2.0              | ```php composer require "darkaonline/l5-swagger:~3.0" ```
 5.4.x    | 2.2       | 1.1, 1.2, 2.0              | ```php composer require "darkaonline/l5-swagger:~4.0" ```
 5.4.x    | 3         | 2.0                        | ```php composer require "darkaonline/l5-swagger:5.4.*" ```
 5.5.x    | 3         | 2.0                        | ```php composer require "darkaonline/l5-swagger:5.5.*" ```


For Swagger 1.0
```php
composer require darkaonline/l5-swagger
```

Open your `AppServiceProvider` (located in `app/Providers`) and add this line in `register` function
```php
$this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
```
or open your `config/app.php` and add this line in `providers` section
```php
L5Swagger\L5SwaggerServiceProvider::class,
```

For Laravel 5.5, no need to manually add `L5SwaggerServiceProvider` into config. It uses package auto discovery feature.

Changes in 5.0
============
- Swagger UI 3.
- Configuration changes.
- Assets dependency dropped. Now includes from composer package.
- [See migration](#migrate-from-3040-to-50)

Changes in 4.0
============
- Laravel 5.4 support

Changes in 3.2.1
============
- Middleware support for routes (#43) (@tantam)

Changes in 3.2
============
- Allow to change swagger base path in generation process
- Allow to define constants in config which can be used later in annotations
- Tests fix form L5.3 and PHP >= 5.6
- Update swagger UI to 2.1.5

Changes in 3.1
============
- Closure routes moved to controller and got names (thanks to @bbs-smuller [#19](https://github.com/DarkaOnLine/L5-Swagger/pull/19))
- Added option to rename generated API .json file name

Changes in 3.0
============
- More accurate naming and structured config
- Swagger UI - v2.1.4
- Tests

Migrate from 2.0 to 3.0
============
- Replace `$this->app->register('\Darkaonline\L5Swagger\L5SwaggerServiceProvider');` with `$this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);` in your `AppServiceProvider`
or add `\L5Swagger\L5SwaggerServiceProvider::class` line in your `config/app.php` file
- Run `l5-swagger:publish-config` to publish new config and make your changes if needed
- Remove `public/vendor/l5-swagger` directory
- Remove `resources/views/vendor/l5-swagger` directory
- Run `l5-swagger:publish-assets` to publish new swagger-ui assets
- Run `l5-swagger:publish-views` to publish new views

Migrate from 3.0|4.0 to 5.0
============
- Remove `config/l5-swagger.php` file (make a copy if needed)
- Remove `public/vendor/l5-swagger` directory
- Remove `resources/views/vendor/l5-swagger` directory
- Run `l5-swagger:publish` to publish new swagger-ui view and configuration
- Edit your `config/l5-swagger.php` file

Configuration
============
- Run `l5-swagger:publish` to publish everything
- Run `l5-swagger:publish-config` to publish configs (`config/l5-swagger.php`)
- Run `l5-swagger:publish-assets` to publish swagger-ui to your public folder (`public/vendor/l5-swagger`)
- Run `l5-swagger:publish-views` to publish views (`resources/views/vendor/l5-swagger`) - only for versions <= 4.0
- Run `l5-swagger:generate` to generate docs or set `generate_always` param to `true` in your config or .env file 

Swagger-php
======================
The actual Swagger spec is beyond the scope of this package. All L5-Swagger does is package up swagger-php and swagger-ui in a Laravel-friendly fashion, and tries to make it easy to serve. For info on how to use swagger-php [look here](http://zircote.com/swagger-php/). For good examples of swagger-php in action [look here](https://github.com/zircote/swagger-php/tree/master/Examples/petstore.swagger.io).

## Support on Beerpay
Hey dude! Help me out for a couple of :beers:!

[![Beerpay](https://beerpay.io/DarkaOnLine/L5-Swagger/badge.svg?style=beer-square)](https://beerpay.io/DarkaOnLine/L5-Swagger)  [![Beerpay](https://beerpay.io/DarkaOnLine/L5-Swagger/make-wish.svg?style=flat-square)](https://beerpay.io/DarkaOnLine/L5-Swagger?focus=wish)
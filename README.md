[![Total Downloads](https://poser.pugx.org/DarkaOnLine/L5-Swagger/downloads.svg)](https://packagist.org/packages/DarkaOnLine/L5-Swagger)
[![Build Status](https://travis-ci.org/DarkaOnLine/L5-Swagger.svg?branch=master)](https://travis-ci.org/DarkaOnLine/L5-Swagger)
[![Coverage Status](https://coveralls.io/repos/github/DarkaOnLine/L5-Swagger/badge.svg?branch=master)](https://coveralls.io/github/DarkaOnLine/L5-Swagger?branch=master)
[![Code Climate](https://codeclimate.com/github/DarkaOnLine/L5-Swagger/badges/gpa.svg)](https://codeclimate.com/github/DarkaOnLine/L5-Swagger)
[![StyleCI](https://styleci.io/repos/32315619/shield)](https://styleci.io/repos/32315619)
[![Dependency Status](https://beta.gemnasium.com/badges/github.com/DarkaOnLine/L5-Swagger.svg)](https://beta.gemnasium.com/projects/github.com/DarkaOnLine/L5-Swagger)

L5 Swagger - OpenApi or Swagger Specification for your Laravel project made easy.
==========

Swagger 2.0 for Laravel >=5.1

This package is a wrapper of [Swagger-php](https://github.com/zircote/swagger-php) and [swagger-ui](https://github.com/swagger-api/swagger-ui) adapted to work with Laravel 5.

Installation
============

 Laravel          | Swagger UI| OpenAPI Spec compatibility | L5-Swagger
:-----------------|:----------|:---------------------------|:----------
 6.0.x            | 3         | 3.0, 2.0                   | `composer require "darkaonline/l5-swagger"`<br><br>:warning: !!! run `composer require 'zircote/swagger-php:2.*'` if you need old **@SWG (SWAGGER annotations)** support. !!!
 5.8.x            | 3         | 3.0, 2.0                   | `composer require "darkaonline/l5-swagger:5.8.*"`<br><br>:warning: !!! run `composer require 'zircote/swagger-php:2.*'` if you need old **@SWG (SWAGGER annotations)** support. !!!
 5.7.x OR 5.6.x   | 3         | 3.0, 2.0                   | `composer require "darkaonline/l5-swagger:5.7.*"`<br><br>:warning: !!! run `composer require 'zircote/swagger-php:2.*'` if you need old **@SWG (SWAGGER annotations)** support. !!!
 5.6.x            | 3         | 2.0                        | `composer require "darkaonline/l5-swagger:5.6.*"`
 5.5.x            | 3         | 2.0                        | `composer require "darkaonline/l5-swagger:5.5.*"`
 5.4.x            | 3         | 2.0                        | `composer require "darkaonline/l5-swagger:5.4.*"`
 5.4.x            | 2.2       | 1.1, 1.2, 2.0              | `composer require "darkaonline/l5-swagger:~4.0"`
 5.3.x            | 2.2       | 1.1, 1.2, 2.0              | `composer require "darkaonline/l5-swagger:~3.0"`
 5.2.x            | 2.2       | 1.1, 1.2, 2.0              | `composer require "darkaonline/l5-swagger:~3.0"`
 5.1.x            | 2.2       | 1.1, 1.2, 2.0              | `composer require "darkaonline/l5-swagger:~3.0"`

You can publish L5-Swagger's config and view files into your project by running:

```bash
$ php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

For Laravel >=5.5, no need to manually add `L5SwaggerServiceProvider` into config. It uses package auto discovery feature. Skip this if you are on >=5.5, if not:

Open your `AppServiceProvider` (located in `app/Providers`) and add this line in `register` function
```php
$this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
```
or open your `config/app.php` and add this line in `providers` section
```php
L5Swagger\L5SwaggerServiceProvider::class,
```

You can access your documentation at `/api/documentation` endpoint.

## Swagger/OpenApi annotations and generating documentation
In order to generate the Swagger/OpenApi documentation for your API, Swagger offers a set of annotations to declare and manipulate the output. These annotations can be added in your controller, model or even a seperate file. An example of [OpenApi annotations can be found here](https://github.com/DarkaOnLine/L5-Swagger/blob/master/tests/storage/annotations/OpenApi/Anotations.php) and [Swagger annotations can be found here](https://github.com/DarkaOnLine/L5-Swagger/blob/master/tests/storage/annotations/Swagger/Anotations.php). For more info check out Swagger's ["pet store" example](https://github.com/zircote/swagger-php/tree/master/Examples/petstore-3.0) or the [Swagger OpenApi Specification](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md).

After the annotiations have been added you can run `php artisan l5-swagger:generate` to generate the documentation. Alternatively, you can set `L5_SWAGGER_GENERATE_ALWAYS` to `true` in your `.env` file so that your documentation will automatically be generated. Make sure your settings in `config/l5-swagger.php` are complete.

I am still using Swagger @SWG annotation
============
If still using Swagger @SWG annotations in you project you should:
- Explicitly require `swagger-php` version 2.* in your projects composer by running:
```bash
composer require 'zircote/swagger-php:2.*'
```
- Set environment variable `SWAGGER_VERSION` to **2.0** in your `.env` file:
```
SWAGGER_VERSION=2.0
```
or in your `config/l5-swagger.php`:
```php
'swagger_version' => env('SWAGGER_VERSION', '2.0'),
```

Using Swagger UI with Passport
============
The easiest way to build and test your Laravel-based API using Swagger-php is to use Passport's `CreateFreshApiToken` middleware. This middleware, built into Laravel's core, adds a cookie to all responses, and the cookie authenticates all subsequent requests through Passport's `TokenGuard`.

To get started, first publish L5-Swagger's config and view files into your own project:

```bash
$ php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

Next, edit your `config/l5-swagger.php` configuration file. Locate the `l5-swagger.routes.middleware` section, and add the following middleware list to the `api` route:

```php
'api' => [
  \App\Http\Middleware\EncryptCookies::class,
  \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
  \Illuminate\Session\Middleware\StartSession::class,
  \Illuminate\View\Middleware\ShareErrorsFromSession::class,
  \App\Http\Middleware\VerifyCsrfToken::class,
  \Illuminate\Routing\Middleware\SubstituteBindings::class,
  \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class,
  'auth',
]
```

TIPS
============

## L5_SWAGGER_GENERATE_ALWAYS

One of the setting I find useful to enable is `l5-swagger.generate_always`, which will cause your Swagger doc to be regenerated each time you load the Swagger UI (<span style="color:OrangeRed">not intended for production use!</span>). All you have to do to enable this in your dev environment is add an environment variable to `.env` named `L5_SWAGGER_GENERATE_ALWAYS` and set it to `true`.

## oauth2 + passport = Bearer \<token\>
Follow instruction in [issue #57](https://github.com/DarkaOnLine/L5-Swagger/issues/57).

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
### For versions < 5.5
- Run `l5-swagger:publish` to publish everything
- Run `l5-swagger:publish-config` to publish configs (`config/l5-swagger.php`)
- Run `l5-swagger:publish-assets` to publish swagger-ui to your public folder (`public/vendor/l5-swagger`)
- Run `l5-swagger:publish-views` to publish views (`resources/views/vendor/l5-swagger`) - only for versions <= 4.0
### For all versions
- Run `l5-swagger:generate` to generate docs or set `generate_always` param to `true` in your config or .env file

Swagger-php
======================
The actual Swagger spec is beyond the scope of this package. All L5-Swagger does is package up swagger-php and swagger-ui in a Laravel-friendly fashion, and tries to make it easy to serve. For info on how to use swagger-php [look here](http://zircote.com/swagger-php/). For good examples of swagger-php in action [look here](https://github.com/zircote/swagger-php/tree/master/Examples/petstore.swagger.io).

## Support on Beerpay
Hey dude! Help me out for a couple of :beers:!

[![Beerpay](https://beerpay.io/DarkaOnLine/L5-Swagger/badge.svg?style=beer-square)](https://beerpay.io/DarkaOnLine/L5-Swagger)  [![Beerpay](https://beerpay.io/DarkaOnLine/L5-Swagger/make-wish.svg?style=flat-square)](https://beerpay.io/DarkaOnLine/L5-Swagger?focus=wish)

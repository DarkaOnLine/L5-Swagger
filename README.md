[![Total Downloads](https://poser.pugx.org/DarkaOnLine/L5-Swagger/downloads.svg)](https://packagist.org/packages/DarkaOnLine/L5-Swagger)

L5 Swagger
==========

Swagger for Laravel 5

This package is a copy of [Swaggervel](https://github.com/slampenny/Swaggervel) adapted to work with Laravel 5 with several tweaks.

Installation
============

- Open your `AppServiceProvider` (located in `app/Providers`) and add this line in `register` function
```php
    $this->app->register('Darkaonline\L5Swagger\L5SwaggerServiceProvider');
```
the final function should similar to this:
```php
    public function register()
    {
        $this->app->bind(
            'Illuminate\Contracts\Auth\Registrar',
            'App\Services\Registrar'
        );

        //Register Swagger Provider
        $this->app->register('Darkaonline\L5Swagger\L5SwaggerServiceProvider');
    }
```

- Run `php artisan l5-swagger:publish-assets` to publish swagger-ui your public folder (`public/vendos/l5-swagger`)

Configuration
============
- Run `l5-swagger:publish-config` to publish configs (`config/l5-swagger.php`)
- Run `l5-swagger:publish-assets` to publish swagger-ui to your public folder (`public/vendos/l5-swagger`)
- Run `l5-swagger:publish-views` to publish views (`resources/views/vendor/l5-swagger`)
- Run `l5-swagger:publish` to publish everything
- Run `l5-swagger:generate` to generate docs


Swagger-php
======================
The actual Swagger spec is beyond the scope of this package. All L5-Swagger does is package up swagger-php and swagger-ui in a Laravel-friendly fashion, and tries to make it easy to serve. For info on how to use swagger-php [look here](http://zircote.com/swagger-php/). For good examples of swagger-php in action [look here](https://github.com/zircote/swagger-php/tree/master/Examples/Petstore).

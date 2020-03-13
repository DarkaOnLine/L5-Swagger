<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'L5Swagger'], function (Router $router) {
    $documentations = config('l5-swagger.documentations', false);

    foreach ($documentations as $name => $config) {
        if (! isset($config['routes'])) {
            continue;
        }

        Route::group([
            'middleware' => \L5Swagger\Http\Middleware\Config::class,
            'documentation' => $name,
        ], function (Router $router) use ($name, $config) {
            if (isset($config['routes']['api'])) {
                $router->get($config['routes']['api'], [
                    'as' => 'l5-swagger.'.$name.'.api',
                    'middleware' => $config['routes']['middleware']['api'] ?? [],
                    'uses' => '\L5Swagger\Http\Controllers\SwaggerController@api',
                ]);
            }

            if (isset($config['routes']['docs'])) {
                $router->get($config['routes']['docs'].'/{jsonFile?}', [
                    'as' => 'l5-swagger.'.$name.'.docs',
                    'middleware' => $config['routes']['middleware']['docs'] ?? [],
                    'uses' => '\L5Swagger\Http\Controllers\SwaggerController@docs',
                ]);

                $router->get($config['routes']['docs'].'/asset/{asset}', [
                    'as' => 'l5-swagger.'.$name.'.asset',
                    'middleware' => $config['routes']['middleware']['asset'] ?? [],
                    'uses' => '\L5Swagger\Http\Controllers\SwaggerAssetController@index',
                ]);
            }

            if (isset($config['routes']['oauth2_callback'])) {
                $router->get($config['routes']['oauth2_callback'], [
                    'as' => 'l5-swagger.'.$name.'.oauth2_callback',
                    'middleware' => $config['routes']['middleware']['oauth2_callback'] ?? [],
                    'uses' => '\L5Swagger\Http\Controllers\SwaggerController@oauth2Callback',
                ]);
            }
        });
    }
});

<?php

$router->any(config('l5-swagger.routes.docs').'/{jsonFile?}', [
    'as' => 'l5-swagger.docs',
    'middleware' => config('l5-swagger.routes.middleware.docs', []),
    'uses' => '\L5Swagger\Http\Controllers\SwaggerController@docs',
]);

$router->get(config('l5-swagger.routes.api'), [
    'as' => 'l5-swagger.api',
    'middleware' => config('l5-swagger.routes.middleware.api', []),
    'uses' => '\L5Swagger\Http\Controllers\SwaggerController@api',
]);

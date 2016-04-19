<?php

$router->any(config('l5-swagger.routes.docs').'/{page?}', '\L5Swagger\Http\Controllers\SwaggerController@docs');

$router->get(config('l5-swagger.routes.api'), '\L5Swagger\Http\Controllers\SwaggerController@api');

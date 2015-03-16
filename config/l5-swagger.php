<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Absolute path to location where parsed swagger annotations will be stored
    |--------------------------------------------------------------------------
    */
    'doc-dir' => storage_path() . '/api-docs',

    /*
    |--------------------------------------------------------------------------
    | Relative path to access parsed swagger annotations.
    |--------------------------------------------------------------------------
    */
    'doc-route' => 'docs',

    /*
    |--------------------------------------------------------------------------
    | Absolute path to directory containing the swagger annotations are stored.
    |--------------------------------------------------------------------------
    */
    "app-dir" => "app",

    /*
    |--------------------------------------------------------------------------
    | Absolute path to directories that you would like to exclude from swagger generation
    |--------------------------------------------------------------------------
    */
    "excludes" => [],

    /*
    |--------------------------------------------------------------------------
    | Turn this off to remove swagger generation on production
    |--------------------------------------------------------------------------
    */
    "generateAlways" => env('SWAGGER_GENERATE_ALWAYS', false),

    /*
    |--------------------------------------------------------------------------
    | Edit to set the api's Auth token
    |--------------------------------------------------------------------------
    */
    "api-key" => env('API_AUTH_TOKEN', 'SomeRandomString'),

    /*
    |--------------------------------------------------------------------------
    | Edit to set the api's version number
    |--------------------------------------------------------------------------
    */
    "default-api-version" => env('DEFAULT_API_VERSION', '1'),

    /*
    |--------------------------------------------------------------------------
    | Edit to set the swagger version number
    |--------------------------------------------------------------------------
    */
    "default-swagger-version" => env('SWAGGER_VERSION', '2.0'),

    /*
    |--------------------------------------------------------------------------
    | Edit to set the api's base path
    |--------------------------------------------------------------------------
    */
    "default-base-path" => "",

    /*
    |--------------------------------------------------------------------------
    | Edit to trust the proxy's ip address - needed for AWS Load Balancer
    |--------------------------------------------------------------------------
    */
    "behind-reverse-proxy" => false,
    /*
    |--------------------------------------------------------------------------
    | Uncomment to add response headers when swagger is generated
    |--------------------------------------------------------------------------
    */
    /*"viewHeaders" => [
        'Content-Type' => 'text/plain'
    ],*/
    /*
    |--------------------------------------------------------------------------
    | Uncomment to add request headers when swagger performs requests
    |--------------------------------------------------------------------------
    */
    /*"requestHeaders" => [
        'TestMe' => 'testValue'
    ],*/
];

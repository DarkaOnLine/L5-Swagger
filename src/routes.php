<?php

use Swagger\Swagger;

$router->any(config('l5-swagger.routes.docs').'/{page?}', function ($page = 'api-docs.json') {
    $filePath = config('l5-swagger.paths.docs')."/{$page}";

    if (File::extension($filePath) === '') {
        $filePath .= '.json';
    }
    if (!File::Exists($filePath)) {
        abort(404, "Cannot find {$filePath}");
    }

    $content = File::get($filePath);

    return Response::make($content, 200, [
        'Content-Type' => 'application/json',
    ]);
});

$router->get(config('l5-swagger.routes.api'), function () {
    if (config('l5-swagger.generate_always')) {
        \L5Swagger\Generator::generateDocs();
    }

    if (config('l5-swagger.proxy')) {
        $proxy = Request::server('REMOTE_ADDR');
        Request::setTrustedProxies([$proxy]);
    }

    $extras = [];
    if (array_key_exists('validatorUrl', config('l5-swagger'))) {
        // This allows for a null value, since this has potentially
        // desirable side effects for swagger.  See the view for more
        // details.
        $extras['validatorUrl'] = config('l5-swagger.validatorUrl');
    }

    //need the / at the end to avoid CORS errors on Homestead systems.
    $response = \Response::make(
        view('l5-swagger::index', [
            'apiKey'             => config('l5-swagger.api.auth_token'),
            'apiKeyVar'          => config('l5-swagger.api.key_var'),
            'securityDefinition' => config('l5-swagger.api.security_definition'),
            'apiKeyInject'       => config('l5-swagger.api.key_inject'),
            'secure'             => Request::secure(),
            'urlToDocs'          => url(config('l5-swagger.routes.docs')),
            'requestHeaders'     => config('l5-swagger.headers.request'),
        ], $extras),
        200
    );

    if (is_array(config('l5-swagger.headers.view')) && !empty(config('l5-swagger.headers.view'))) {
        foreach (config('l5-swagger.headers.view') as $key => $value) {
            $response->header($key, $value);
        }
    }

    return $response;
});

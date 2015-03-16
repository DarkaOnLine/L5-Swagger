<?php

use Swagger\Swagger;

Route::any(Config::get('l5-swagger.doc-route').'/{page?}', function($page='api-docs.json') {
    $filePath = Config::get('l5-swagger.doc-dir') . "/{$page}";

    if (File::extension($filePath) === "") {
        $filePath .= ".json";
    }
    if (!File::Exists($filePath)) {
        App::abort(404, "Cannot find {$filePath}");
    }

    $content = File::get($filePath);
    return Response::make($content, 200, array(
        'Content-Type' => 'application/json'
    ));
});

Route::get('api-docs', function() {
    if (Config::get('l5-swagger.generateAlways')) {
        \Darkaonline\L5Swagger\Generator::generateDocs();
    }

    if (Config::get('l5-swagger.behind-reverse-proxy')) {
        $proxy = Request::server('REMOTE_ADDR');
        Request::setTrustedProxies(array($proxy));
    }

    //need the / at the end to avoid CORS errors on Homestead systems.
    $response = Response::make(
        view('l5-swagger::index', array(
                'secure'         => Request::secure(),
                'urlToDocs'      => url(Config::get('l5-swagger.doc-route')),
                'requestHeaders' => Config::get('l5-swagger.requestHeaders') )
        ),
        200
    );

    if (Config::has('l5-swagger.viewHeaders')) {
        foreach (Config::get('l5-swagger.viewHeaders') as $key => $value) {
            $response->header($key, $value);
        }
    }

    return $response;
});
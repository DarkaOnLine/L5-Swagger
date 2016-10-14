<?php

namespace L5Swagger\Http\Controllers;

use File;
use Request;
use Response;
use L5Swagger\Generator;
use Illuminate\Routing\Controller as BaseController;

class SwaggerController extends BaseController
{
    /**
     * Dump api-docs.json content endpoint.
     *
     * @param string $jsonFile
     *
     * @return \Response
     */
    public function docs($jsonFile = null)
    {
        $filePath = config('l5-swagger.paths.docs').'/'.
            (! is_null($jsonFile) ? $jsonFile : config('l5-swagger.paths.docs_json', 'api-docs.json'));

        if (File::extension($filePath) === '') {
            $filePath .= '.json';
        }
        if (! File::exists($filePath)) {
            abort(404, 'Cannot find '.$filePath);
        }

        $content = File::get($filePath);

        return Response::make($content, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Display Swagger API page.
     *
     * @return \Response
     */
    public function api()
    {
        if (config('l5-swagger.generate_always')) {
            Generator::generateDocs();
        }

        if (config('l5-swagger.proxy')) {
            $proxy = Request::server('REMOTE_ADDR');
            Request::setTrustedProxies([$proxy]);
        }

        $extras = [];
        if (array_key_exists('validatorUrl', config('l5-swagger'))) {
            // This allows for a null value, since this has potentially
            // desirable side effects for swagger. See the view for more
            // details.
            $extras['validatorUrl'] = config('l5-swagger.validatorUrl');
        }

        // Need the / at the end to avoid CORS errors on Homestead systems.
        $response = Response::make(
            view('l5-swagger::index', [
                'apiKey'             => config('l5-swagger.api.auth_token'),
                'apiKeyVar'          => config('l5-swagger.api.key_var'),
                'securityDefinition' => config('l5-swagger.api.security_definition'),
                'apiKeyInject'       => config('l5-swagger.api.key_inject'),
                'secure'             => Request::secure(),
                'urlToDocs'          => route('l5-swagger.docs', config('l5-swagger.paths.docs_json', 'api-docs.json')),
                'requestHeaders'     => config('l5-swagger.headers.request'),
                'docExpansion'       => config('l5-swagger.docExpansion'),
                'highlightThreshold' => config('l5-swagger.highlightThreshold'),
            ], $extras),
            200
        );

        $headersView = config('l5-swagger.headers.view');
        if (is_array($headersView) and ! empty($headersView)) {
            foreach ($headersView as $key => $value) {
                $response->header($key, $value);
            }
        }

        return $response;
    }
}

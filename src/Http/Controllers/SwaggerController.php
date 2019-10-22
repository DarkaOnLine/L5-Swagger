<?php

namespace L5Swagger\Http\Controllers;

use L5Swagger\Generator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use L5Swagger\Exceptions\L5SwaggerException;
use Illuminate\Routing\Controller as BaseController;

class SwaggerController extends BaseController
{
    /**
     * Dump api-docs content endpoint. Supports dumping a json, yml or yaml file.
     *
     * @param string $file
     *
     * @return \Response
     */
    public function docs(string $file = null)
    {
        $targetFile = config('l5-swagger.paths.docs_json', 'api-docs.json');
        $extension = 'json';
        if (!is_null($file)) {
            $targetFile = $file;
            $extension = explode('.', $file)[1];
        }
        $filePath = config('l5-swagger.paths.docs').'/'.$targetFile;

        if (! File::exists($filePath)) {
            try {
                Generator::generateDocs();
            } catch (\Exception $e) {
                abort(404, 'Cannot find '.$filePath.' and cannot be generated.');
            }
        }

        $content = File::get($filePath);

        $contentType = 'application/json';
        if ($extension === 'yaml') {
            // Use text/plain instead of application/yaml to prevent triggering a file
            // download in Firefox
            $contentType = 'text/plain';
        }

        return Response::make($content, 200, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Display Swagger API page.
     *
     * @return \Illuminate\Http\Response
     */
    public function api()
    {
        if ($proxy = config('l5-swagger.proxy')) {
            if (! is_array($proxy)) {
                $proxy = [$proxy];
            }
            \Illuminate\Http\Request::setTrustedProxies($proxy, \Illuminate\Http\Request::HEADER_X_FORWARDED_ALL);
        }

        // Need the / at the end to avoid CORS errors on Homestead systems.
        $response = Response::make(
            view('l5-swagger::index', [
                'secure' => Request::secure(),
                'urlToDocs' => route('l5-swagger.docs', config('l5-swagger.paths.docs_json', 'api-docs.json')),
                'operationsSorter' => config('l5-swagger.operations_sort'),
                'configUrl' => config('l5-swagger.additional_config_url'),
                'validatorUrl' => config('l5-swagger.validator_url'),
            ]),
            200
        );

        return $response;
    }

    /**
     * Display Oauth2 callback pages.
     *
     * @return string
     * @throws L5SwaggerException
     */
    public function oauth2Callback()
    {
        return File::get(swagger_ui_dist_path('oauth2-redirect.html'));
    }
}

<?php

namespace L5Swagger\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\Generator;

class SwaggerController extends BaseController
{
    /**
     * Dump api-docs content endpoint. Supports dumping a json, or yaml file.
     *
     * @param string $file
     *
     * @return \Response
     */
    public function docs(string $file = null)
    {
        $extension = 'json';
        $targetFile = config('l5-swagger.paths.docs_json', 'api-docs.json');

        if (! is_null($file)) {
            $targetFile = $file;
            $extension = explode('.', $file)[1];
        }

        $filePath = config('l5-swagger.paths.docs').'/'.$targetFile;

        if (config('l5-swagger.generate_always') || ! File::exists($filePath)) {
            try {
                Generator::generateDocs();
            } catch (\Exception $e) {
                Log::error($e);

                abort(
                    404,
                    sprintf(
                        'Unable to generate documentation file to: "%s". Please make sure directory is writable. Error: %s',
                        $filePath,
                        $e->getMessage()
                    )
                );
            }
        }

        $content = File::get($filePath);

        if ($extension === 'yaml') {
            return Response::make($content, 200, [
                'Content-Type' => 'application/yaml',
                'Content-Disposition' => 'inline',
            ]);
        }

        return Response::make($content, 200, [
            'Content-Type' => 'application/json',
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

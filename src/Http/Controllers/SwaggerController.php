<?php

namespace L5Swagger\Http\Controllers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\Response as ResponseFacade;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\GeneratorFactory;

class SwaggerController extends BaseController
{
    /**
     * @var GeneratorFactory
     */
    protected $generatorFactory;

    public function __construct(GeneratorFactory $generatorFactory)
    {
        $this->generatorFactory = $generatorFactory;
    }

    /**
     * Dump api-docs content endpoint. Supports dumping a json, or yaml file.
     *
     * @param  Request  $request
     * @param  ?string  $file
     * @return Response
     *
     * @throws L5SwaggerException
     * @throws FileNotFoundException
     */
    public function docs(Request $request, string $file = null)
    {
        $fileSystem = new Filesystem();
        $documentation = $request->offsetGet('documentation');
        $config = $request->offsetGet('config');

        $targetFile = $config['paths']['docs_json'] ?? 'api-docs.json';
        $yaml = false;

        if ($file !== null) {
            $targetFile = $file;
            $parts = explode('.', $file);

            if (! empty($parts)) {
                $extension = array_pop($parts);
                $yaml = strtolower($extension) === 'yaml';
            }
        }

        $filePath = $config['paths']['docs'].'/'.$targetFile;

        if ($config['generate_always']) {
            $generator = $this->generatorFactory->make($documentation);

            try {
                $generator->generateDocs();
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

        if (! $fileSystem->exists($filePath)) {
            abort(404, sprintf('Unable to locate documentation file at: "%s"', $filePath));
        }

        $content = $fileSystem->get($filePath);

        if ($yaml) {
            return ResponseFacade::make($content, 200, [
                'Content-Type' => 'application/yaml',
                'Content-Disposition' => 'inline',
            ]);
        }

        return ResponseFacade::make($content, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Display Swagger API page.
     *
     * @param  Request  $request
     * @return Response
     */
    public function api(Request $request)
    {
        $documentation = $request->offsetGet('documentation');
        $config = $request->offsetGet('config');

        if ($proxy = $config['proxy']) {
            if (! is_array($proxy)) {
                $proxy = [$proxy];
            }
            Request::setTrustedProxies(
                $proxy,
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
            );
        }

        $urlToDocs = $this->generateDocumentationFileURL($documentation, $config);
        $useAbsolutePath = config('l5-swagger.documentations.'.$documentation.'.paths.use_absolute_path', true);

        // Need the / at the end to avoid CORS errors on Homestead systems.
        return ResponseFacade::make(
            view('l5-swagger::index', [
                'documentation' => $documentation,
                'secure' => RequestFacade::secure(),
                'urlToDocs' => $urlToDocs,
                'operationsSorter' => $config['operations_sort'],
                'configUrl' => $config['additional_config_url'],
                'validatorUrl' => $config['validator_url'],
                'useAbsolutePath' => $useAbsolutePath,
            ]),
            200
        );
    }

    /**
     * Display Oauth2 callback pages.
     *
     * @param  Request  $request
     * @return string
     *
     * @throws L5SwaggerException
     * @throws FileNotFoundException
     */
    public function oauth2Callback(Request $request)
    {
        $fileSystem = new Filesystem();
        $documentation = $request->offsetGet('documentation');

        return $fileSystem->get(swagger_ui_dist_path($documentation, 'oauth2-redirect.html'));
    }

    /**
     * Generate URL for documentation file.
     *
     * @param  string  $documentation
     * @param  array  $config
     * @return string
     */
    protected function generateDocumentationFileURL(string $documentation, array $config)
    {
        $fileUsedForDocs = $config['paths']['docs_json'] ?? 'api-docs.json';

        if (! empty($config['paths']['format_to_use_for_docs'])
            && $config['paths']['format_to_use_for_docs'] === 'yaml'
            && $config['paths']['docs_yaml']
        ) {
            $fileUsedForDocs = $config['paths']['docs_yaml'];
        }

        $useAbsolutePath = config('l5-swagger.documentations.'.$documentation.'.paths.use_absolute_path', true);

        return route(
            'l5-swagger.'.$documentation.'.docs',
            $fileUsedForDocs,
            $useAbsolutePath
        );
    }
}

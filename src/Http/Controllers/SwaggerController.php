<?php

namespace L5Swagger\Http\Controllers;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request as RequestFacade;
use L5Swagger\ConfigFactory;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\GeneratorFactory;

class SwaggerController extends BaseController
{
    public function __construct(
        private readonly GeneratorFactory $generatorFactory,
        private readonly ConfigFactory $configFactory
    ) {
    }

    /**
     * Handles requests for API documentation and returns the corresponding file content.
     *
     * @param  Request  $request  The HTTP request containing parameters such as documentation and configuration.
     * @return Response The HTTP response containing the documentation file content with appropriate headers.
     *
     * @throws FileNotFoundException If the documentation file does not exist.
     * @throws Exception If the documentation generation process fails.
     */
    public function docs(Request $request): Response
    {
        $fileSystem = new Filesystem();
        $documentation = $request->offsetGet('documentation');
        $config = $request->offsetGet('config');

        $formatToUseForDocs = $config['paths']['format_to_use_for_docs'] ?? 'json';
        $yamlFormat = ($formatToUseForDocs === 'yaml');

        $filePath = sprintf(
            '%s/%s',
            $config['paths']['docs'],
            $yamlFormat ? $config['paths']['docs_yaml'] : $config['paths']['docs_json']
        );

        if ($config['generate_always']) {
            $generator = $this->generatorFactory->make($documentation);

            try {
                $generator->generateDocs();
            } catch (Exception $e) {
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

        if ($yamlFormat) {
            return response($content, 200, [
                'Content-Type' => 'application/yaml',
                'Content-Disposition' => 'inline',
            ]);
        }

        return response($content, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Handles the API request and renders the Swagger documentation view.
     *
     * @param  Request  $request  The HTTP request containing necessary parameters such as documentation and configuration details.
     * @return Response The HTTP response rendering the Swagger documentation view.
     */
    public function api(Request $request): Response
    {
        $documentation = $request->offsetGet('documentation');
        $config = $request->offsetGet('config');
        $proxy = $config['proxy'];

        if ($proxy) {
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
        $urlsToDocs = $this->getAllDocumentationUrls();
        $useAbsolutePath = config('l5-swagger.documentations.'.$documentation.'.paths.use_absolute_path', true);

        // Need the / at the end to avoid CORS errors on Homestead systems.
        return response(
            view('l5-swagger::index', [
                'documentation' => $documentation,
                'documentationTitle' => $config['api']['title'] ?? $documentation,
                'secure' => RequestFacade::secure(),
                'urlToDocs' => $urlToDocs, // Is not used in the view, but still passed for backwards compatibility
                'urlsToDocs' => $urlsToDocs,
                'operationsSorter' => $config['operations_sort'],
                'configUrl' => $config['additional_config_url'],
                'validatorUrl' => $config['validator_url'],
                'useAbsolutePath' => $useAbsolutePath,
            ]),
            200
        );
    }

    /**
     * Handles the OAuth2 callback and retrieves the required file for the redirect.
     *
     * @param  Request  $request  The HTTP request containing necessary parameters.
     * @return string The content of the OAuth2 redirect file.
     *
     * @throws FileNotFoundException
     * @throws L5SwaggerException
     */
    public function oauth2Callback(Request $request): string
    {
        $fileSystem = new Filesystem();
        $documentation = $request->offsetGet('documentation');

        return $fileSystem->get(swagger_ui_dist_path($documentation, 'oauth2-redirect.html'));
    }

    /**
     * Generate the URL for accessing the documentation file based on the provided configuration.
     *
     * @param  string  $documentation  The name of the documentation instance.
     * @param  array<string,mixed>  $config  The configuration settings for generating the documentation URL.
     * @return string The generated URL for the documentation file.
     */
    protected function generateDocumentationFileURL(string $documentation, array $config): string
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

    /**
     * Retrieves all available documentation URLs with their corresponding titles.
     *
     * @return array<string,string> An associative array where the keys are documentation titles
     *                              and the values are the corresponding URLs.
     *
     * @throws L5SwaggerException
     */
    protected function getAllDocumentationUrls(): array
    {
        /** @var array<string> $documentations */
        $documentations = array_keys(config('l5-swagger.documentations', []));

        $urlsToDocs = [];

        foreach ($documentations as $documentationName) {
            $config = $this->configFactory->documentationConfig($documentationName);
            $title = $config['api']['title'] ?? $documentationName;

            $urlsToDocs[$title] = $this->generateDocumentationFileURL($documentationName, $config);
        }

        return $urlsToDocs;
    }
}

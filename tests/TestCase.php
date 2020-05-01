<?php

namespace Tests;

use L5Swagger\ConfigFactory;
use L5Swagger\Generator;
use L5Swagger\L5SwaggerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var array
     */
    protected $defaultConfig;

    /**
     * @var Generator
     */
    protected $generator;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeConfigFactory();
        $this->makeGenerator();

        $this->copyAssets();
    }

    public function tearDown(): void
    {
        $config = $this->configFactory->documentationConfig();

        if (file_exists($this->jsonDocsFile())) {
            unlink($this->jsonDocsFile());
        }

        if (file_exists($this->yamlDocsFile())) {
            unlink($this->yamlDocsFile());
        }

        if (file_exists($config['paths']['docs'])) {
            rmdir($config['paths']['docs']);
        }

        parent::tearDown();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            L5SwaggerServiceProvider::class,
        ];
    }

    /**
     * Create json docs file.
     */
    protected function crateJsonDocumentationFile(): void
    {
        file_put_contents($this->jsonDocsFile(), '{}');
    }

    /**
     * Get path for json docs file.
     *
     * @return string
     */
    protected function jsonDocsFile(): string
    {
        $config = $this->configFactory->documentationConfig();
        $docs = $config['paths']['docs'];

        if (! is_dir($docs)) {
            mkdir($docs);
        }

        return $docs.DIRECTORY_SEPARATOR.$config['paths']['docs_json'];
    }

    /**
     * Get path for yaml docs file.
     *
     * @return string
     */
    protected function yamlDocsFile(): string
    {
        $config = $this->configFactory->documentationConfig();
        $docs = $config['paths']['docs'];

        if (! is_dir($docs)) {
            mkdir($docs);
        }

        return $docs.DIRECTORY_SEPARATOR.$config['paths']['docs_yaml'];
    }

    /**
     * Prepare config for testing.
     */
    protected function setAnnotationsPath(): void
    {
        $cfg = config('l5-swagger.documentations.default');
        $cfg['paths']['annotations'] = __DIR__.'/storage/annotations/OpenApi';
        $cfg['generate_always'] = true;
        $cfg['generate_yaml_copy'] = true;

        //Adding constants which will be replaced in generated json file
        $cfg['constants']['L5_SWAGGER_CONST_HOST'] = 'http://my-default-host.com';

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $cfg,
            ],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->makeGenerator();
    }

    /**
     * Make Config Factory.
     */
    protected function makeConfigFactory(): void
    {
        $this->configFactory = $this->app->make(ConfigFactory::class);
    }

    /**
     * Make Generator.
     */
    protected function makeGenerator(): void
    {
        $this->generator = $this->app->make(Generator::class);
    }

    /**
     * @param string $fileName
     */
    protected function setCustomDocsFileName(string $fileName): void
    {
        $cfg = config('l5-swagger.documentations.default');
        $cfg['paths']['docs_json'] = $fileName;
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $cfg,
            ],
            'defaults' => config('l5-swagger.defaults'),
        ]]);
    }

    /**
     * Copy assets from vendor to testbench.
     */
    protected function copyAssets(): void
    {
        $src = __DIR__.'/../vendor/swagger-api/swagger-ui/dist/';
        $destination = __DIR__.'/../vendor/orchestra/testbench-core/laravel/vendor/swagger-api/swagger-ui/dist/';

        if (! is_dir($destination)) {
            $base = realpath(
                __DIR__.'/../vendor/orchestra/testbench-core/laravel/vendor'
            );

            mkdir($base = $base.'/swagger-api');
            mkdir($base = $base.'/swagger-ui');
            mkdir($base = $base.'/dist');
        }

        foreach (scandir($src) as $file) {
            $filePath = $src.$file;

            if (! is_readable($filePath) || is_dir($filePath)) {
                continue;
            }

            copy(
                $filePath,
                $destination.$file
            );
        }
    }
}

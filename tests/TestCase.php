<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use L5Swagger\ConfigFactory;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\Generator;
use L5Swagger\L5SwaggerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionObject;

class TestCase extends OrchestraTestCase
{
    protected ConfigFactory $configFactory;

    protected array $defaultConfig;

    protected Generator $generator;

    protected Filesystem|MockObject $fileSystem;

    #[Before]
    public function setUpFileSystem(): void
    {
        $this->fileSystem = $this->createMock(Filesystem::class);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->makeConfigFactory();
        $this->makeGenerator();

        $this->copyAssets();
    }

    public function tearDown(): void
    {
        $fileSystem = new Filesystem();

        try {
            $config = $this->configFactory->documentationConfig();

            if ($fileSystem->exists($this->jsonDocsFile())) {
                $fileSystem->delete($this->jsonDocsFile());
            }

            if ($fileSystem->exists($this->yamlDocsFile())) {
                $fileSystem->delete($this->yamlDocsFile());
            }

            if ($fileSystem->exists($config['paths']['docs'])) {
                $fileSystem->deleteDirectory($config['paths']['docs']);
            }
        } catch (L5SwaggerException $e) {
        }

        parent::tearDown();
    }

    /**
     * @param  Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [
            L5SwaggerServiceProvider::class,
        ];
    }

    /**
     * Create json docs file.
     *
     * @throws L5SwaggerException
     */
    protected function crateJsonDocumentationFile(): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->put($this->jsonDocsFile(), '{}');
    }

    /**
     * Create json docs file.
     *
     * @throws L5SwaggerException
     */
    protected function createYamlDocumentationFile(): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->put($this->yamlDocsFile(), '');
    }

    /**
     * Get path for json docs file.
     *
     *
     * @throws L5SwaggerException
     */
    protected function jsonDocsFile(): string
    {
        $fileSystem = new Filesystem();
        $config = $this->configFactory->documentationConfig();
        $docs = $config['paths']['docs'];

        if (! $fileSystem->isDirectory($docs)) {
            $fileSystem->makeDirectory($docs);
        }

        return $docs.DIRECTORY_SEPARATOR.$config['paths']['docs_json'];
    }

    /**
     * Get path for yaml docs file.
     *
     *
     * @throws L5SwaggerException
     */
    protected function yamlDocsFile(): string
    {
        $fileSystem = new Filesystem();
        $config = $this->configFactory->documentationConfig();
        $docs = $config['paths']['docs'];

        if (! $fileSystem->isDirectory($docs)) {
            $fileSystem->makeDirectory($docs);
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

    protected function makeGeneratorWithMockedFileSystem(): void
    {
        $this->generator = $this->app->make(Generator::class);

        $reflectionObject = new ReflectionObject($this->generator);
        $reflectionProperty = $reflectionObject->getProperty('fileSystem');
        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($this->generator, $this->fileSystem);
    }

    protected function setCustomDocsFileName(string $fileName, string $type = 'json'): void
    {
        $cfg = config('l5-swagger.documentations.default');

        if ($type === 'json') {
            $cfg['paths']['format_to_use_for_docs'] = $type;
            $cfg['paths']['docs_json'] = $fileName;
        }

        if ($type === 'yaml') {
            $cfg['paths']['format_to_use_for_docs'] = $type;
            $cfg['paths']['docs_yaml'] = $fileName;
        }

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
        $fileSystem = new Filesystem();
        $src = __DIR__.'/../vendor/swagger-api/swagger-ui/dist/';
        $destination = __DIR__.'/../vendor/orchestra/testbench-core/laravel/vendor/swagger-api/swagger-ui/dist/';

        if (! $fileSystem->isDirectory($destination)) {
            $base = realpath(
                __DIR__.'/../vendor/orchestra/testbench-core'
            );

            $fileSystem->makeDirectory(
                $base.'/laravel/vendor/swagger-api/swagger-ui/dist',
                0777,
                true
            );
        }

        foreach (scandir($src) as $file) {
            $filePath = $src.$file;

            if (! $fileSystem->isReadable($filePath) || $fileSystem->isDirectory($filePath)) {
                continue;
            }

            $fileSystem->copy(
                $filePath,
                $destination.$file
            );
        }
    }
}

<?php

namespace Tests;

use L5Swagger\L5SwaggerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->copyAssets();
    }

    public function tearDown()
    {
        if (file_exists($this->jsonDocsFile())) {
            unlink($this->jsonDocsFile());
        }

        if (file_exists($this->yamlDocsFile())) {
            unlink($this->yamlDocsFile());
        }

        if (file_exists(config('l5-swagger.paths.docs'))) {
            rmdir(config('l5-swagger.paths.docs'));
        }

        parent::tearDown();
    }

    protected function isOpenApi()
    {
        return version_compare(config('l5-swagger.swagger_version'), '3.0', '>=');
    }

    protected function getPackageProviders($app)
    {
        return [
            L5SwaggerServiceProvider::class,
        ];
    }

    protected function crateJsonDocumentationFile()
    {
        file_put_contents($this->jsonDocsFile(), '{}');
    }

    protected function jsonDocsFile()
    {
        if (! is_dir(config('l5-swagger.paths.docs'))) {
            mkdir(config('l5-swagger.paths.docs'));
        }

        return config('l5-swagger.paths.docs').'/'.config('l5-swagger.paths.docs_json');
    }

    protected function yamlDocsFile()
    {
        if (! is_dir(config('l5-swagger.paths.docs'))) {
            mkdir(config('l5-swagger.paths.docs'));
        }

        return config('l5-swagger.paths.docs').'/'.config('l5-swagger.paths.docs_yaml');
    }

    protected function setAnnotationsPath()
    {
        $cfg = config('l5-swagger');
        $cfg['paths']['annotations'] = __DIR__.'/storage/annotations/Swagger';

        if ($this->isOpenApi()) {
            $cfg['paths']['annotations'] = __DIR__.'/storage/annotations/OpenApi';
        }

        $cfg['generate_always'] = true;
        $cfg['generate_yaml_copy'] = true;

        //Adding constants which will be replaced in generated json file
        $cfg['constants']['L5_SWAGGER_CONST_HOST'] = 'http://my-default-host.com';

        config(['l5-swagger' => $cfg]);
    }

    protected function setCustomDocsFileName($fileName)
    {
        $cfg = config('l5-swagger');
        $cfg['paths']['docs_json'] = $fileName;
        config(['l5-swagger' => $cfg]);
    }

    protected function copyAssets()
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

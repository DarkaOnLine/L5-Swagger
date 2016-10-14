<?php

class TestCase extends Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            L5Swagger\L5SwaggerServiceProvider::class,
        ];
    }

    public function tearDown()
    {
        if (file_exists($this->jsonDocsFile())) {
            unlink($this->jsonDocsFile());
            rmdir(config('l5-swagger.paths.docs'));
        }
        parent::tearDown();
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

    protected function setAnnotationsPath()
    {
        $cfg = config('l5-swagger');
        $cfg['paths']['annotations'] = __DIR__.'/storage/annotations';
        $cfg['generate_always'] = true;

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
}

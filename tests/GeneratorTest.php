<?php

class GeneratorTest extends \TestCase
{
    /** @test */
    public function can_generate_api_json_file()
    {
        $this->setAnnotationsPath();

        \L5Swagger\Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->visit(route('l5-swagger.docs'))
            ->see('L5 Swagger API')
            ->see('http://my-default-host.com')
            ->assertResponseOk();
    }

    /** @test */
    public function can_generate_api_json_file_with_changed_base_path()
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $cfg['paths']['base'] = '/new/api/base/path';
        config(['l5-swagger' => $cfg]);

        \L5Swagger\Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->visit(route('l5-swagger.docs'))
            ->see('L5 Swagger API')
            ->see('/new/api/base/path')
            ->assertResponseOk();
    }
}

<?php

class GeneratorTest extends \TestCase
{
    /** @test */
    public function can_generate_api_json_file()
    {
        $this->setAnnotationsPath();

        \L5Swagger\Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
            ->assertSee('L5 Swagger API')
            ->assertSee('http://my-default-host.com')
            ->isOk();
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

        $this->get(route('l5-swagger.docs'))
            ->assertSee('L5 Swagger API')
            ->assertSee('/new/api/base/path')
            ->isOk();
    }

    /** @test */
    public function can_set_proxy()
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $cfg['proxy'] = 'http://proxy.dev';
        config(['l5-swagger' => $cfg]);

        $this->get(route('l5-swagger.api'))
            ->isOk();

        $this->assertTrue(file_exists($this->jsonDocsFile()));
    }

    /** @test */
    public function can_set_validator_url()
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $cfg['validatorUrl'] = 'http://validator-url.dev';
        config(['l5-swagger' => $cfg]);

        $this->get(route('l5-swagger.api'))
            ->assertSee('http://validator-url.dev')
            ->isOk();

        $this->assertTrue(file_exists($this->jsonDocsFile()));
    }

    /** @test */
    public function can_set_custom_response_header()
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $cfg['headers']['view'] = [
            'header1' => 'param1',
            'header2' => 'param2',
        ];
        config(['l5-swagger' => $cfg]);

        $this->get(route('l5-swagger.api'))
            ->assertHeader('header1', 'param1')
            ->assertHeader('header2', 'param2')
            ->isOk();

        $this->assertTrue(file_exists($this->jsonDocsFile()));
    }
}

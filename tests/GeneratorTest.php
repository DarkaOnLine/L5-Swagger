<?php

namespace Tests;

use L5Swagger\Generator;

class GeneratorTest extends TestCase
{
    /** @test */
    public function canGenerateApiJsonFile()
    {
        $this->setAnnotationsPath();

        Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
            ->assertSee('L5 Swagger API')
            ->assertSee('my-default-host.com')
            ->isOk();
    }

    /** @test */
    public function canGenerateApiJsonFileWithChangedBasePath()
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $cfg['paths']['base'] = '/new_path/is/here';
        config(['l5-swagger' => $cfg]);

        Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
            ->assertSee('L5 Swagger API')
            ->assertSee('new_path')
            ->isOk();
    }

    /** @test */
    public function canSetProxy()
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
    public function canSetValidatorUrl()
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $cfg['validator_url'] = 'http://validator-url.dev';
        config(['l5-swagger' => $cfg]);

        $this->get(route('l5-swagger.api'))
            ->assertSee('validator-url.dev')
            ->isOk();

        $this->assertTrue(file_exists($this->jsonDocsFile()));
    }

    /** @test */
    public function canGenerateApiJsonFileWithSecurityDefinition()
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $security = [
            'new_api_key_securitye' => [
                'type' => 'apiKey',
                'name' => 'api_key_name',
                'in' => 'query',
            ],
        ];
        $cfg['security'] = $security;
        config(['l5-swagger' => $cfg]);

        Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
            ->assertSee('new_api_key_securitye')
            ->assertJsonFragment($security)
            ->isOk();
    }
}

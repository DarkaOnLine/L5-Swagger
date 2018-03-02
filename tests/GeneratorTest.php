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
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->isOk();
    }

    /** @test */
    public function canGenerateApiJsonFileWithChangedBasePath()
    {
        if ($this->isOpenApi() == true) {
            $this->markTestSkipped('only for openApi 2.0');
        }

        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $cfg['paths']['base'] = '/new_path/is/here';
        config(['l5-swagger' => $cfg]);

        Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
            ->assertSee('L5 Swagger')
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
}

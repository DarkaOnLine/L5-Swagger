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
            ->assertSee('http://my-default-host.com')
            ->isOk();
    }

    /** @test */
    public function canGenerateApiJsonFileWithChangedBasePath()
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $cfg['paths']['base'] = '/new/api/base/path';
        config(['l5-swagger' => $cfg]);

        Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
            ->assertSee('L5 Swagger API')
            ->assertSee('/new/api/base/path')
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
        $cfg['validatorUrl'] = 'http://validator-url.dev';
        config(['l5-swagger' => $cfg]);

        $this->get(route('l5-swagger.api'))
            ->assertSee('http://validator-url.dev')
            ->isOk();

        $this->assertTrue(file_exists($this->jsonDocsFile()));
    }

    /** @test */
    public function canSetCustomResponseHeader()
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

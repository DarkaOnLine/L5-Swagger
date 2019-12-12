<?php

namespace Tests;

use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\Generator;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class GeneratorTest extends TestCase
{
    /** @test **/
    public function itThrowsExceptionIfDocumatationDirIsNotWritable()
    {
        $this->setAnnotationsPath();

        mkdir(config('l5-swagger.paths.docs'), 0555);
        chmod(config('l5-swagger.paths.docs'), 0555);

        $this->expectException(L5SwaggerException::class);

        Generator::generateDocs();

        chmod(config('l5-swagger.paths.docs'), 0777);
    }

    /** @test */
    public function canGenerateApiJsonFile()
    {
        $this->setAnnotationsPath();

        Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));
        $this->assertTrue(file_exists($this->yamlDocsFile()));

        $this->get(route('l5-swagger.docs'))
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->assertStatus(200);

        $this->get(route('l5-swagger.docs', ['jsonFile' => config('l5-swagger.paths.docs_yaml')]))
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->assertStatus(200);
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
            ->assertStatus(200);

        $this->get(route('l5-swagger.docs', ['jsonFile' => config('l5-swagger.paths.docs_yaml')]))
            ->assertSee('L5 Swagger')
            ->assertSee('new_path')
            ->assertStatus(200);
    }

    /** @test */
    public function canGenerateApiJsonFileWithChangedBaseServer()
    {
        if (! $this->isOpenApi()) {
            $this->markTestSkipped('only for openApi 3.0');
        }

        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $cfg['paths']['base'] = 'https://test-server.url';
        $cfg['swagger_version'] = '3.0';
        config(['l5-swagger' => $cfg]);

        tap(new Generator)->generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
            ->assertSee('https://test-server.url')
            ->assertSee('https://projects.dev/api/v1')
            ->assertDontSee('basePath')
            ->assertStatus(200);

        $this->get(route('l5-swagger.docs', ['jsonFile' => config('l5-swagger.paths.docs_yaml')]))
            ->assertSee('https://test-server.url')
            ->assertSee('https://projects.dev/api/v1')
            ->assertDontSee('basePath')
            ->assertStatus(200);
    }

    /** @test */
    public function canSetProxy()
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger');
        $proxy = '99.56.62.66';
        $cfg['proxy'] = $proxy;
        config(['l5-swagger' => $cfg]);

        $this->get(route('l5-swagger.api'))
            ->assertStatus(200);

        $this->assertEquals(\Request::getTrustedProxies()[0], $proxy);

        $this->get(route('l5-swagger.docs'))
            ->assertStatus(200);

        $this->assertTrue(file_exists($this->jsonDocsFile()));
        $this->assertTrue(file_exists($this->yamlDocsFile()));
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
            ->assertStatus(200);

        $this->get(route('l5-swagger.api', ['jsonFile' => config('l5-swagger.paths.docs_yaml')]))
            ->assertSee('validator-url.dev')
            ->assertStatus(200);

        $this->get(route('l5-swagger.docs'))
            ->assertStatus(200);

        $this->assertTrue(file_exists($this->jsonDocsFile()));
        $this->assertTrue(file_exists($this->yamlDocsFile()));
    }

    /** @test */
    public function canAppropriateYamlType()
    {
        $this->setAnnotationsPath();

        Generator::generateDocs();

        $objects = (new Parser())->parse(file_get_contents($this->yamlDocsFile()), Yaml::PARSE_OBJECT_FOR_MAP);

        $actual = $objects->paths->{'/projects'}->get->security[0]->api_key_security_example;
        $this->assertIsArray($actual);
    }
}

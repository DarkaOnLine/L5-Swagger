<?php

namespace Tests;

use L5Swagger\Exceptions\L5SwaggerException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class GeneratorTest extends TestCase
{
    /** @test **/
    public function itThrowsExceptionIfDocumentationDirIsNotWritable(): void
    {
        $this->setAnnotationsPath();

        mkdir(config('l5-swagger.documentations.default.paths.docs'), 0555);
        chmod(config('l5-swagger.documentations.default.paths.docs'), 0555);

        $this->expectException(L5SwaggerException::class);
        $this->expectExceptionMessage('Documentation storage directory is not writable');

        $this->generator->generateDocs();

        chmod(config('l5-swagger.documentations.default.paths.docs'), 0777);
    }

    /** @test */
    public function canGenerateApiJsonFile(): void
    {
        $this->setAnnotationsPath();

        $this->generator->generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));
        $this->assertTrue(file_exists($this->yamlDocsFile()));

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->assertStatus(200);

        $jsonFile = config('l5-swagger.documentations.default.paths.docs_yaml');
        $this->get(route('l5-swagger.default.docs', ['jsonFile' => $jsonFile]))
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->assertStatus(200);
    }

    /** @test */
    public function canGenerateApiJsonFileWithChangedBaseServer(): void
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger.documentations.default');
        $cfg['paths']['base'] = 'https://test-server.url';
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $cfg,
            ],
        ]]);

        $this->generator->generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('https://test-server.url')
            ->assertSee('https://projects.dev/api/v1')
            ->assertDontSee('basePath')
            ->assertStatus(200);

        $jsonFile = config('l5-swagger.documentations.default.paths.docs_yaml');
        $this->get(route('l5-swagger.default.docs', ['jsonFile' => $jsonFile]))
            ->assertSee('https://test-server.url')
            ->assertSee('https://projects.dev/api/v1')
            ->assertDontSee('basePath')
            ->assertStatus(200);
    }

    /** @test */
    public function canSetProxy(): void
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger.documentations.default');
        $proxy = '99.56.62.66';
        $cfg['proxy'] = $proxy;
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $cfg,
            ],
        ]]);

        $this->get(route('l5-swagger.default.api'))
            ->assertStatus(200);

        $this->assertEquals(\Request::getTrustedProxies()[0], $proxy);

        $this->get(route('l5-swagger.default.docs'))
            ->assertStatus(200);

        $this->assertTrue(file_exists($this->jsonDocsFile()));
        $this->assertTrue(file_exists($this->yamlDocsFile()));
    }

    /** @test */
    public function canSetValidatorUrl(): void
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger.documentations.default');
        $cfg['validator_url'] = 'http://validator-url.dev';
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $cfg,
            ],
        ]]);

        $this->get(route('l5-swagger.default.api'))
            ->assertSee('validator-url.dev')
            ->assertStatus(200);

        $jsonFile = config('l5-swagger.documentations.default.paths.docs_yaml');
        $this->get(route('l5-swagger.default.api', ['jsonFile' => $jsonFile]))
            ->assertSee('validator-url.dev')
            ->assertStatus(200);

        $this->get(route('l5-swagger.default.docs'))
            ->assertStatus(200);

        $this->assertTrue(file_exists($this->jsonDocsFile()));
        $this->assertTrue(file_exists($this->yamlDocsFile()));
    }

    /** @test */
    public function canAppropriateYamlType(): void
    {
        $this->setAnnotationsPath();

        $this->generator->generateDocs();

        $objects = (new Parser())->parse(file_get_contents($this->yamlDocsFile()), Yaml::PARSE_OBJECT_FOR_MAP);

        $actual = $objects->paths->{'/projects'}->get->security[0]->api_key_security_example;
        $this->assertIsArray($actual);
    }
}

<?php

namespace Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use L5Swagger\Exceptions\L5SwaggerException;
use OpenApi\Analysers\TokenAnalyser;
use OpenApi\Processors\CleanUnmerged;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class GeneratorTest extends TestCase
{
    /** @test **/
    public function itThrowsExceptionIfDocumentationDirIsNotWritable(): void
    {
        $this->setAnnotationsPath();

        $config = $this->configFactory->documentationConfig();
        $docs = $config['paths']['docs'];

        File::shouldReceive('exists')
            ->once()
            ->with($docs)
            ->andReturn(true);

        File::shouldReceive('isWritable')
            ->once()
            ->with($docs)
            ->andReturn(false);

        $this->expectException(L5SwaggerException::class);
        $this->expectExceptionMessage('Documentation storage directory is not writable');

        $this->generator->generateDocs();
    }

    /** @test **/
    public function itWillCreateDocumentationDirIfItIsWritable(): void
    {
        $this->setAnnotationsPath();

        $config = $this->configFactory->documentationConfig();
        $docs = $config['paths']['docs'];

        File::shouldReceive('exists')
            ->times(3)
            ->with($docs)
            ->andReturnValues([true, false, true]);

        File::shouldReceive('isWritable')
            ->once()
            ->with($docs)
            ->andReturn(true);

        File::shouldReceive('makeDirectory')
            ->once()
            ->with($docs);

        mkdir($docs, 0777);

        $this->generator->generateDocs();
    }

    /** @test **/
    public function itThrowsExceptionIfDocumentationDirWasNotCreated(): void
    {
        $this->setAnnotationsPath();

        $config = $this->configFactory->documentationConfig();
        $docs = $config['paths']['docs'];

        File::shouldReceive('exists')
            ->times(3)
            ->with($docs)
            ->andReturnValues([true, false, false]);

        File::shouldReceive('isWritable')
            ->once()
            ->with($docs)
            ->andReturn(true);

        File::shouldReceive('makeDirectory')
            ->once()
            ->with($docs);

        $this->expectException(L5SwaggerException::class);
        $this->expectExceptionMessage('Documentation storage directory could not be created');

        $this->generator->generateDocs();
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
            ->assertSee('getProjectsList')
            ->assertSee('getProductsList')
            ->assertSee('getClientsList')
            ->assertStatus(200);

        $config = $this->configFactory->documentationConfig();
        $jsonFile = $config['paths']['docs_yaml'];
        $this->get(route('l5-swagger.default.docs', ['jsonFile' => $jsonFile]))
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->assertSee('getProjectsList')
            ->assertSee('getProductsList')
            ->assertSee('getClientsList')
            ->assertStatus(200);
    }

    /** @test */
    public function canGenerateWithLegacyExcludedDirectories(): void
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger.documentations.default');
        $cfg['paths']['excludes'] = [
            __DIR__.'/storage/annotations/OpenApi/Clients',
        ];
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $cfg,
            ],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->generator->generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->assertSee('getProjectsList')
            ->assertSee('getProductsList')
            ->assertDontSee('getClientsList')
            ->assertStatus(200);
    }

    /** @test */
    public function canGenerateWithScanOptions(): void
    {
        $cfg = config('l5-swagger.documentations.default');

        $cfg['scanOptions']['exclude'] = [
            __DIR__.'/storage/annotations/OpenApi/Clients',
        ];

        $cfg['scanOptions']['pattern'] = 'L5SwaggerAnnotationsExample*.*';
        $cfg['scanOptions']['analyser'] = new TokenAnalyser;
        $cfg['scanOptions']['processors'] = [
            new CleanUnmerged,
        ];

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $cfg,
            ],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->setAnnotationsPath();

        $this->generator->generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->assertSee('getProjectsList')
            ->assertSee('getProductsList')
            ->assertDontSee('getClientsList')
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
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->generator->generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('https://test-server.url')
            ->assertSee('https://projects.dev/api/v1')
            ->assertDontSee('basePath')
            ->assertStatus(200);

        $config = $this->configFactory->documentationConfig();
        $jsonFile = $config['paths']['docs_yaml'];
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
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->get(route('l5-swagger.default.api'))
            ->assertStatus(200);

        $this->assertEquals(Request::getTrustedProxies()[0], $proxy);

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
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->get(route('l5-swagger.default.api'))
            ->assertSee('validator-url.dev')
            ->assertStatus(200);

        $config = $this->configFactory->documentationConfig();
        $jsonFile = $config['paths']['docs_yaml'];
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

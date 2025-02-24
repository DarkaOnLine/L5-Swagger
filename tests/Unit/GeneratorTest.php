<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\Generator;
use L5Swagger\GeneratorFactory;
use L5Swagger\L5SwaggerServiceProvider;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\OpenApiException;
use OpenApi\Processors\CleanUnmerged;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

#[TestDox('Generator')]
#[CoversClass(GeneratorFactory::class)]
#[CoversClass(Generator::class)]
#[CoversClass(L5SwaggerServiceProvider::class)]
class GeneratorTest extends TestCase
{
    /**
     * @throws L5SwaggerException
     */
    public function testItThrowsExceptionIfDocumentationDirIsNotWritable(): void
    {
        $this->setAnnotationsPath();

        $config = $this->configFactory->documentationConfig();
        $docs = $config['paths']['docs'];

        $this->fileSystem
            ->expects($this->once())
            ->method('exists')
            ->with($docs)
            ->willReturn(true);

        $this->fileSystem
            ->expects($this->once())
            ->method('isWritable')
            ->with($docs)
            ->willReturn(false);

        $this->expectException(L5SwaggerException::class);
        $this->expectExceptionMessage('Documentation storage directory is not writable');

        $this->makeGeneratorWithMockedFileSystem();
        $this->generator->generateDocs();
    }

    /**
     * @throws L5SwaggerException
     */
    public function testItWillCreateDocumentationDirIfItIsWritable(): void
    {
        $this->setAnnotationsPath();

        $config = $this->configFactory->documentationConfig();
        $docs = $config['paths']['docs'];

        $this->fileSystem
            ->expects($this->exactly(3))
            ->method('exists')
            ->with($docs)
            ->willReturnOnConsecutiveCalls(true, false, true);

        $this->fileSystem
            ->expects($this->once())
            ->method('isWritable')
            ->with($docs)
            ->willReturn(true);

        $this->fileSystem
            ->expects($this->once())
            ->method('makeDirectory')
            ->with($docs);

        mkdir($docs, 0777);

        $this->makeGeneratorWithMockedFileSystem();
        $this->generator->generateDocs();
    }

    /**
     * @throws L5SwaggerException
     */
    public function testItThrowsExceptionIfDocumentationDirWasNotCreated(): void
    {
        $this->setAnnotationsPath();

        $config = $this->configFactory->documentationConfig();
        $docs = $config['paths']['docs'];

        $this->fileSystem
            ->expects($this->exactly(3))
            ->method('exists')
            ->with($docs)
            ->willReturnOnConsecutiveCalls(true, false, false);

        $this->fileSystem
            ->expects($this->once())
            ->method('isWritable')
            ->with($docs)
            ->willReturn(true);

        $this->fileSystem
            ->expects($this->once())
            ->method('makeDirectory')
            ->with($docs);

        $this->expectException(L5SwaggerException::class);
        $this->expectExceptionMessage('Documentation storage directory could not be created');

        $this->makeGeneratorWithMockedFileSystem();
        $this->generator->generateDocs();
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCanGenerateApiJsonFile(): void
    {
        $this->setAnnotationsPath();

        $this->generator->generateDocs();

        $this->assertFileExists($this->jsonDocsFile());
        $this->assertFileExists($this->yamlDocsFile());

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->assertSee('getProjectsList')
            ->assertSee('Get list of products')
            ->assertSee('getClientsList')
            ->assertStatus(200);

        $config = $this->configFactory->documentationConfig();
        $jsonFile = $config['paths']['docs_yaml'];
        $this->get(route('l5-swagger.default.docs', ['jsonFile' => $jsonFile]))
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->assertSee('getProjectsList')
            ->assertSee('Get list of products')
            ->assertSee('getClientsList')
            ->assertStatus(200);
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCanGenerateWithLegacyExcludedDirectories(): void
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger.documentations.default');
        $cfg['paths']['excludes'] = [
            __DIR__.'/../storage/annotations/OpenApi/Clients',
        ];
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $cfg,
            ],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->generator->generateDocs();

        $this->assertFileExists($this->jsonDocsFile());

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('L5 Swagger')
            ->assertSee('my-default-host.com')
            ->assertSee('getProjectsList')
            ->assertSee('Get list of products')
            ->assertDontSee('getClientsList')
            ->assertStatus(200);
    }

    /**
     * @throws L5SwaggerException
     * @throws OpenApiException
     */
    public function testCanGenerateWithScanOptions(): void
    {
        $cfg = config('l5-swagger.documentations.default');

        $cfg['scanOptions'] = [
            'exclude' => [__DIR__.'/../storage/annotations/OpenApi/Clients'],
            'analyser' => new ReflectionAnalyser([
                new AttributeAnnotationFactory(),
                new DocBlockAnnotationFactory(),
            ]),
            'open_api_spec_version' => '3.1.0',
            'processors' => [new CleanUnmerged],
            'default_processors_configuration' => ['operationId' => ['hash' => false]],
        ];

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => ['default' => $cfg],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->setAnnotationsPath();

        $this->generator->generateDocs();

        $this->assertFileExists($this->jsonDocsFile());

        $response = $this->get(route('l5-swagger.default.docs'));

        $response->assertSee('L5 Swagger')
            ->assertSee('3.1.0')
            ->assertSee('my-default-host.com')
            ->assertSee('getProjectsList')
            ->assertSee('operationId')
            ->assertSee("POST::/products::Tests\\\storage\\\annotations\\\OpenApi\\\Products\\\L5SwaggerAnnotationsExampleProducts::getProductsList")
            ->assertDontSee('getClientsList')
            ->assertStatus(200);
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCanGenerateApiJsonFileWithChangedBaseServer(): void
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

        $this->assertFileExists($this->jsonDocsFile());

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

    /**
     * @throws L5SwaggerException
     */
    public function testCanSetProxy(): void
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

        $this->assertSame(Request::getTrustedProxies()[0], $proxy);

        $this->get(route('l5-swagger.default.docs'))
            ->assertStatus(200);

        $this->assertFileExists($this->jsonDocsFile());
        $this->assertFileExists($this->yamlDocsFile());
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCanSetValidatorUrl(): void
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

        $this->assertFileExists($this->jsonDocsFile());
        $this->assertFileExists($this->yamlDocsFile());
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCanAppropriateYamlType(): void
    {
        $this->setAnnotationsPath();

        $this->generator->generateDocs();

        $content = file_get_contents($this->yamlDocsFile());

        if (! \is_string($content)) {
            $this->fail('File content is not string');
        }

        $objects = (new Parser())->parse($content, Yaml::PARSE_OBJECT_FOR_MAP);

        $actual = $objects->paths->{'/projects'}->get->security[0]->api_key_security_example;
        $this->assertIsArray($actual);
    }
}

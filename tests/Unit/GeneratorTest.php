<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use L5Swagger\CustomBuilderInterface;
use L5Swagger\CustomGeneratorInterface;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\Generator;
use L5Swagger\GeneratorFactory;
use L5Swagger\L5SwaggerServiceProvider;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\Analysis;
use OpenApi\Attributes as OA;
use OpenApi\Builder;
use OpenApi\Builder\Mode;
use OpenApi\Context;
use OpenApi\Generator as OpenApiGenerator;
use OpenApi\OpenApiException;
use OpenApi\Processors\AugmentParameters;
use OpenApi\Processors\AugmentSchemas;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Tests\Unit\Fixtures\TestCustomBuilder;
use Tests\Unit\Fixtures\TestCustomGenerator;

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
    public function testGeneratedDocumentMatchesTheSnapshot(): void
    {
        $this->setAnnotationsPath();

        $this->generator->generateDocs();

        $this->assertSame(
            json_decode((string) file_get_contents(__DIR__.'/Fixtures/api-docs.json'), true),
            json_decode((string) file_get_contents($this->jsonDocsFile()), true)
        );
    }

    /**
     * @throws L5SwaggerException
     */
    public function testSwaggerPhpWarningIsThrown(): void
    {
        $this->setAnnotationsPath();
        $this->setScanOptions([], __DIR__.'/../storage/annotations/Warning');

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('requires at least one');

        $this->generator->generateDocs();
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCanGenerateWithAnalysis(): void
    {
        $context = new Context();
        $analysis = new Analysis([], $context);
        $analysis->addAnnotation(new OA\Server(url: 'https://analysis.test', description: 'From the analysis'), $context);

        $this->setAnnotationsPath();
        $this->setScanOptions(['analysis' => $analysis]);

        $this->generator->generateDocs();

        $document = json_decode((string) file_get_contents($this->jsonDocsFile()), true);

        $this->assertContains(['url' => 'https://analysis.test', 'description' => 'From the analysis'], $document['servers']);
        $this->assertArrayHasKey('/projects', $document['paths']);
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
            'processors' => [new AugmentParameters],
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
    public function testCanGenerateWithProcessorPositioning(): void
    {
        $cfg = config('l5-swagger.documentations.default');

        $cfg['scanOptions'] = [
            'processors' => [
                new AugmentParameters(),
                ['class' => AugmentSchemas::class, 'after' => AugmentParameters::class],
            ],
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

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('L5 Swagger')
            ->assertStatus(200);
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCanGenerateWithCustomGeneratorFactory(): void
    {
        $cfg = config('l5-swagger.documentations.default');

        $factory = new class implements CustomGeneratorInterface
        {
            public bool $called = false;

            public function create(): OpenApiGenerator
            {
                $this->called = true;

                return new OpenApiGenerator();
            }
        };

        $cfg['scanOptions'] = [
            'generator_factory' => $factory,
        ];

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => ['default' => $cfg],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->setAnnotationsPath();

        $this->generator->generateDocs();

        $this->assertTrue($factory->called);
        $this->assertFileExists($this->jsonDocsFile());

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('L5 Swagger')
            ->assertStatus(200);
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCanGenerateWithCustomGeneratorFactoryClassName(): void
    {
        $cfg = config('l5-swagger.documentations.default');

        $cfg['scanOptions'] = [
            'generator_factory' => TestCustomGenerator::class,
        ];

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => ['default' => $cfg],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->setAnnotationsPath();

        $this->generator->generateDocs();

        $this->assertFileExists($this->jsonDocsFile());

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('L5 Swagger')
            ->assertStatus(200);
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCustomBuilderFactoryInClassicModeMatchesTheGenerator(): void
    {
        $this->setAnnotationsPath();
        $this->generator->generateDocs();
        $expected = json_decode((string) file_get_contents($this->jsonDocsFile()), true);

        $factory = new class implements CustomBuilderInterface
        {
            public bool $called = false;

            public function create(): Builder
            {
                $this->called = true;

                return new Builder();
            }
        };

        $this->setScanOptions(['builder_factory' => $factory]);
        $this->generator->generateDocs();

        $this->assertTrue($factory->called);
        $this->assertSame($expected, json_decode((string) file_get_contents($this->jsonDocsFile()), true));
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCanGenerateWithCustomBuilderFactoryClassName(): void
    {
        $this->setAnnotationsPath();
        $this->setScanOptions(['builder_factory' => TestCustomBuilder::class]);

        $this->generator->generateDocs();

        $this->assertFileExists($this->jsonDocsFile());

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('L5 Swagger')
            ->assertStatus(200);
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCustomBuilderFactoryInClassicModeAddsTheBaseServer(): void
    {
        $this->setAnnotationsPath();
        $this->setScanOptions(
            ['builder_factory' => TestCustomBuilder::class],
            base: 'https://test-server.url'
        );

        $this->generator->generateDocs();

        $document = json_decode((string) file_get_contents($this->jsonDocsFile()), true);

        $this->assertContains(['url' => 'https://test-server.url'], $document['servers']);
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCustomBuilderFactoryInSpecMode(): void
    {
        $this->setAnnotationsPath();
        $this->setScanOptions(
            ['builder_factory' => new class implements CustomBuilderInterface
            {
                public function create(): Builder
                {
                    return (new Builder())->setMode(Mode::SPEC);
                }
            }],
            __DIR__.'/../storage/annotations/Spec',
            'https://test-server.url',
            ['securityDefinitions' => ['securitySchemes' => [
                'api_key' => ['type' => 'apiKey', 'name' => 'api_key', 'in' => 'header'],
            ]]]
        );

        $this->generator->generateDocs();

        $document = json_decode((string) file_get_contents($this->jsonDocsFile()), true);

        $this->assertSame('3.0.0', $document['openapi']);
        $this->assertSame(['openapi', 'info', 'servers', 'paths', 'components'], array_keys($document));
        $this->assertSame('apiKey', $document['components']['securitySchemes']['api_key']['type']);
        $this->assertSame(
            [['url' => 'http://my-default-host.com'], ['url' => 'https://test-server.url']],
            $document['servers']
        );
        $this->assertSame('listProjects', $document['paths']['/projects']['get']['operationId']);
        $this->assertFileExists($this->yamlDocsFile());
    }

    public function testCustomBuilderFactoryCannotBeCombinedWithGeneratorFactory(): void
    {
        $this->setAnnotationsPath();
        $this->setScanOptions([
            'builder_factory' => TestCustomBuilder::class,
            'generator_factory' => TestCustomGenerator::class,
        ]);

        $this->expectException(L5SwaggerException::class);
        $this->expectExceptionMessage('not both');

        $this->generator->generateDocs();
    }

    public function testCustomBuilderFactoryMustImplementTheInterface(): void
    {
        $this->setAnnotationsPath();
        $this->setScanOptions(['builder_factory' => TestCustomGenerator::class]);

        $this->expectException(L5SwaggerException::class);
        $this->expectExceptionMessage(CustomBuilderInterface::class);

        $this->generator->generateDocs();
    }

    /**
     * Replace the default documentation's scan options, and optionally its paths and other settings, and rebuild the generator.
     *
     * @param  array<string,mixed>  $scanOptions
     * @param  array<string,mixed>  $overrides
     */
    protected function setScanOptions(array $scanOptions, ?string $annotations = null, ?string $base = null, array $overrides = []): void
    {
        $cfg = array_replace(config('l5-swagger.documentations.default'), $overrides);
        $cfg['scanOptions'] = $scanOptions;
        if ($annotations !== null) {
            $cfg['paths']['annotations'] = $annotations;
        }
        if ($base !== null) {
            $cfg['paths']['base'] = $base;
        }

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => ['default' => $cfg],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->makeGenerator();
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
    public function testPopulateServersInitializesServersWhenUndefined(): void
    {
        $cfg = config('l5-swagger.documentations.default');
        $cfg['paths']['annotations'] = __DIR__.'/../storage/annotations/OpenApiNoServer';
        $cfg['paths']['base'] = 'https://test-server.url';
        $cfg['generate_always'] = true;
        $cfg['generate_yaml_copy'] = false;
        $cfg['constants']['L5_SWAGGER_CONST_HOST'] = 'https://my-default-host.com';

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => ['default' => $cfg],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->makeGenerator();
        $this->generator->generateDocs();

        $this->assertFileExists($this->jsonDocsFile());

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('https://test-server.url')
            ->assertStatus(200);
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

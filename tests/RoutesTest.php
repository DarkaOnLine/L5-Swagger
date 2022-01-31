<?php

namespace Tests;

use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\Generator;
use L5Swagger\GeneratorFactory;
use PHPUnit\Framework\MockObject\MockObject;

class RoutesTest extends TestCase
{
    /** @test */
    public function userCantAccessJsonFileIfItIsNotGenerated(): void
    {
        $jsonUrl = route('l5-swagger.default.docs');

        $response = $this->get($jsonUrl);
        $this->assertTrue($response->isNotFound());
    }

    /** @test */
    public function userCanAccessJsonFileIfItIsGenerated(): void
    {
        $jsonUrl = route('l5-swagger.default.docs');
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => config('l5-swagger.documentations'),
            'defaults' => array_merge(config('l5-swagger.defaults'), ['generate_always' => false]),
        ]]);

        $this->crateJsonDocumentationFile();

        $this->get($jsonUrl)
            ->assertSee('{}')
            ->assertHeader('Content-Type', 'application/json')
            ->isOk();
    }

    /** @test */
    public function userCanAccessAndGenerateCustomJsonFile(): void
    {
        $customJsonFileName = 'docs.v1.json';

        $jsonUrl = route('l5-swagger.default.docs', $customJsonFileName);

        $this->setCustomDocsFileName($customJsonFileName);

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => config('l5-swagger.documentations'),
            'defaults' => array_merge(config('l5-swagger.defaults'), ['generate_always' => false]),
        ]]);

        $this->crateJsonDocumentationFile();

        $this->get($jsonUrl)
            ->assertSee('{}')
            ->assertHeader('Content-Type', 'application/json')
            ->isOk();
    }

    /** @test */
    public function userCanAccessAndGenerateYamlFile(): void
    {
        $customYamlFileName = 'docs.yaml';

        $jsonUrl = route('l5-swagger.default.docs', $customYamlFileName);

        $this->setCustomDocsFileName($customYamlFileName, 'yaml');

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => config('l5-swagger.documentations'),
            'defaults' => array_merge(config('l5-swagger.defaults'), ['generate_always' => false]),
        ]]);

        $this->createYamlDocumentationFile();

        $this->get($jsonUrl)
            ->assertHeader('Content-Type', 'application/yaml')
            ->isOk();
    }

    /** @test */
    public function itCanAccessAndGenerateYamlFile(): void
    {
        $customYamlFileName = 'docs.yaml';

        $jsonUrl = route('l5-swagger.default.api');

        $this->setCustomDocsFileName($customYamlFileName, 'yaml');

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => config('l5-swagger.documentations'),
            'defaults' => array_merge(config('l5-swagger.defaults'), ['generate_always' => true]),
        ]]);

        $this->setAnnotationsPath();

        $this->get($jsonUrl)
            ->assertSeeText('http://localhost/docs/docs.yaml')
            ->isOk();
    }

    /** @test */
    public function userCanAccessDocumentationFileWithoutExtensionIfItExists(): void
    {
        $customYamlFileName = 'docs-file-without-extension';

        $jsonUrl = route('l5-swagger.default.docs', $customYamlFileName);

        $this->setCustomDocsFileName($customYamlFileName);

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => config('l5-swagger.documentations'),
            'defaults' => array_merge(config('l5-swagger.defaults'), ['generate_always' => false]),
        ]]);

        $this->crateJsonDocumentationFile();

        $this->get($jsonUrl)
            ->assertHeader('Content-Type', 'application/json')
            ->isOk();
    }

    /** @test */
    public function itDoesNotThrowExceptionOnDocsFileWithoutExtension(): void
    {
        $fileWithoutExtension = 'docs';

        $jsonUrl = route('l5-swagger.default.docs', $fileWithoutExtension);

        $this->crateJsonDocumentationFile();

        $this->get($jsonUrl)
            ->assertNotFound()
            ->isOk();
    }

    /**
     * @test
     *
     * @throws L5SwaggerException
     */
    public function userCanAccessDocumentationInterface(): void
    {
        $config = $this->configFactory->documentationConfig();
        $jsonFile = $config['paths']['docs_json'] ?? 'api-docs.json';

        $this->get($config['routes']['api'])
            ->assertSee(route('l5-swagger.default.docs', $jsonFile))
            ->assertSee(route('l5-swagger.default.oauth2_callback'))
            ->isOk();
    }

    /**
     * @test
     *
     * @throws L5SwaggerException
     */
    public function itCanServeAssets(): void
    {
        $this->get(l5_swagger_asset('default', 'swagger-ui.css'))
            ->assertSee('.swagger-ui')
            ->isOk();
    }

    /** @test */
    public function itWillThrowExceptionForIncorrectAsset(): void
    {
        $this->expectException(L5SwaggerException::class);
        $this->expectExceptionMessage('(bad-swagger-ui.css) - this L5 Swagger asset is not allowed');

        l5_swagger_asset('default', 'bad-swagger-ui.css');
    }

    /** @test */
    public function itHandleBadAssetRequest(): void
    {
        $this->get(route('l5-swagger.default.asset', 'file.css'))
            ->assertNotFound();
    }

    /** @test */
    public function userCanAccessOauth2Redirect(): void
    {
        $this->get(route('l5-swagger.default.oauth2_callback'))
            ->assertSee('swaggerUIRedirectOauth2')
            ->assertSee('oauth2.auth.code')
            ->isOk();
    }

    /** @test */
    public function itWillReturn404ForIncorrectJsonFile(): void
    {
        $jsonUrl = route('l5-swagger.default.docs', 'invalid.json');

        $this->get($jsonUrl)->assertNotFound();
    }

    /** @test */
    public function itWillNotAttemptDocGenerationWhenAlwaysGenerateIsDisabled(): void
    {
        $jsonUrl = route('l5-swagger.default.docs', 'unknown_file.json');
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => config('l5-swagger.documentations'),
            'defaults' => array_merge(config('l5-swagger.defaults'), ['generate_always' => false]),
        ]]);

        $mockGenerator = $this->mockGenerator();
        $mockGenerator->expects($this->never())->method('generateDocs');

        $this->get($jsonUrl)->assertNotFound();
    }

    /** @test */
    public function itWillReturn404WhenDocGenerationFails(): void
    {
        $jsonUrl = route('l5-swagger.default.docs', 'docs');
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => config('l5-swagger.documentations'),
            'defaults' => array_merge(config('l5-swagger.defaults'), ['generate_always' => true]),
        ]]);

        $mockGenerator = $this->mockGenerator();
        $mockGenerator->expects($this->once())->method('generateDocs')->will($this->throwException(new \Exception));

        $this->get($jsonUrl)->assertNotFound();
    }

    /**
     * @return MockObject&Generator
     */
    private function mockGenerator()
    {
        $mockGenerator = $this->createMock(Generator::class);
        $mockGeneratorFactory = $this->createMock(GeneratorFactory::class);
        $mockGeneratorFactory->expects($this->any())->method('make')->willReturn($mockGenerator);
        app()->extend(GeneratorFactory::class, function () use ($mockGeneratorFactory) {
            return $mockGeneratorFactory;
        });

        return $mockGenerator;
    }
}

<?php

namespace Tests;

use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\Generator;
use L5Swagger\GeneratorFactory;
use L5Swagger\Http\Controllers\SwaggerAssetController;
use L5Swagger\Http\Controllers\SwaggerController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Exception;

/**
 * @covers \L5Swagger\Http\Controllers\SwaggerController
 * @covers \L5Swagger\Http\Controllers\SwaggerAssetController
 */
#[TestDox('Routes')]
#[CoversClass(SwaggerController::class)]
#[CoversClass(SwaggerAssetController::class)]
class RoutesTest extends TestCase
{
    public function testUserCantAccessJsonFileIfItIsNotGenerated(): void
    {
        $jsonUrl = route('l5-swagger.default.docs');

        $response = $this->get($jsonUrl);
        $this->assertTrue($response->isNotFound());
    }

    /**
     * @throws L5SwaggerException
     */
    public function testUserCanAccessJsonFileIfItIsGenerated(): void
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

    /**
     * @throws L5SwaggerException
     */
    public function testUserCanAccessAndGenerateCustomJsonFile(): void
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

    /**
     * @throws L5SwaggerException
     */
    public function testUserCanAccessAndGenerateYamlFile(): void
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

    public function testItCanAccessAndGenerateYamlFile(): void
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

    /**
     * @throws L5SwaggerException
     */
    public function testUserCanAccessDocumentationFileWithoutExtensionIfItExists(): void
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

    /**
     * @throws L5SwaggerException
     */
    public function testItDoesNotThrowExceptionOnDocsFileWithoutExtension(): void
    {
        $fileWithoutExtension = 'docs';

        $jsonUrl = route('l5-swagger.default.docs', $fileWithoutExtension);

        $this->crateJsonDocumentationFile();

        $this->get($jsonUrl)
            ->assertNotFound()
            ->isOk();
    }

    /**
     * @throws L5SwaggerException
     */
    public function testUserCanAccessDocumentationInterface(): void
    {
        $config = $this->configFactory->documentationConfig();
        $jsonFile = $config['paths']['docs_json'] ?? 'api-docs.json';

        $this->get($config['routes']['api'])
            ->assertSee(route('l5-swagger.default.docs', $jsonFile))
            ->assertSee(route('l5-swagger.default.oauth2_callback'))
            ->isOk();
    }

    /**
     * @throws L5SwaggerException
     */
    public function testItCanServeAssets(): void
    {
        $this->get(l5_swagger_asset('default', 'swagger-ui.css'))
            ->assertSee('.swagger-ui')
            ->isOk();
    }

    public function testItWillThrowExceptionForIncorrectAsset(): void
    {
        $this->expectException(L5SwaggerException::class);
        $this->expectExceptionMessage('(bad-swagger-ui.css) - this L5 Swagger asset is not allowed');

        l5_swagger_asset('default', 'bad-swagger-ui.css');
    }

    public function testItHandleBadAssetRequest(): void
    {
        $this->get(route('l5-swagger.default.asset', 'file.css'))
            ->assertNotFound();
    }

    public function testUserCanAccessOauth2Redirect(): void
    {
        $this->get(route('l5-swagger.default.oauth2_callback'))
            ->assertSee('swaggerUIRedirectOauth2')
            ->assertSee('oauth2.auth.code')
            ->isOk();
    }

    public function testItWillReturn404ForIncorrectJsonFile(): void
    {
        $jsonUrl = route('l5-swagger.default.docs', 'invalid.json');

        $this->get($jsonUrl)->assertNotFound();
    }

    public function testItWillNotAttemptDocGenerationWhenAlwaysGenerateIsDisabled(): void
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

    public function testItWillReturn404WhenDocGenerationFails(): void
    {
        $jsonUrl = route('l5-swagger.default.docs', 'docs');
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => config('l5-swagger.documentations'),
            'defaults' => array_merge(config('l5-swagger.defaults'), ['generate_always' => true]),
        ]]);

        $mockGenerator = $this->mockGenerator();
        $mockGenerator->expects($this->once())->method('generateDocs')->willThrowException(new \Exception);

        $this->get($jsonUrl)->assertNotFound();
    }

    /**
     * @throws Exception
     */
    private function mockGenerator(): Generator
    {
        $mockGenerator = $this->createMock(Generator::class);
        $mockGeneratorFactory = $this->createMock(GeneratorFactory::class);
        $mockGeneratorFactory->method('make')->willReturn($mockGenerator);
        app()->extend(GeneratorFactory::class, function () use ($mockGeneratorFactory) {
            return $mockGeneratorFactory;
        });

        return $mockGenerator;
    }
}

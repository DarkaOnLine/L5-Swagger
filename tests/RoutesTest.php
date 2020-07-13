<?php

namespace Tests;

use L5Swagger\Exceptions\L5SwaggerException;

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
        $this->createYamlDocumentationFile();

        $this->get($jsonUrl)
            ->assertHeader('Content-Type', 'application/yaml')
            ->isOk();
    }

    /** @test */
    public function userCanAccessDocumentationFileWithoutExtensionIfItExists(): void
    {
        $customYamlFileName = 'docs-file-without-extension';

        $jsonUrl = route('l5-swagger.default.docs', $customYamlFileName);

        $this->setCustomDocsFileName($customYamlFileName);
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
}

<?php

namespace Tests;

class RoutesTest extends TestCase
{
    /** @test */
    public function userCantAccessJsonFileIfItIsNotGenerated()
    {
        $jsonUrl = route('l5-swagger.docs');

        $response = $this->get($jsonUrl);
        $this->assertTrue($response->isNotFound());
    }

    /** @test */
    public function userCanAccessJsonFileIfItIsGenerated()
    {
        $jsonUrl = route('l5-swagger.docs');

        $this->crateJsonDocumentationFile();

        $this->get($jsonUrl)
            ->assertSee('{}')
            ->isOk();
    }

    /** @test */
    public function userCanAccessAndGenerateCustomJsonFile()
    {
        $customJsonFileName = 'docs.v1.json';

        $jsonUrl = route('l5-swagger.docs', $customJsonFileName);

        $this->setCustomDocsFileName($customJsonFileName);
        $this->crateJsonDocumentationFile();

        $this->get($jsonUrl)
            ->assertSee('{}')
            ->isOk();
    }

    /** @test */
    public function userCanAccessDocumentationInterface()
    {
        $this->get(config('l5-swagger.routes.api'))
            ->assertSee(route('l5-swagger.docs', config('l5-swagger.paths.docs_json', 'api-docs.json')))
            ->assertSee(route('l5-swagger.oauth2_callback'))
            ->isOk();
    }

    /** @test */
    public function itCanServeAssets()
    {
        $this->get(l5_swagger_asset('swagger-ui.css'))
            ->assertSee('.swagger-ui')
            ->isOk();
    }

    /** @test */
    public function userCanAccessOauth2Redirect()
    {
        $this->get(route('l5-swagger.oauth2_callback'))
            ->assertSee('swaggerUIRedirectOauth2')
            ->assertSee('oauth2.auth.code')
            ->isOk();
    }
}

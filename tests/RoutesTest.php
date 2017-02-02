<?php

namespace Tests;

class RoutesTest extends TestCase
{
    /** @test */
    public function userCantAccessJsonFileIfItIsNotGenerated()
    {
        $jsonUrl = route('l5-swagger.docs');

        $this->get($jsonUrl)
            ->isNotFound();
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
            ->isOk();
    }
}

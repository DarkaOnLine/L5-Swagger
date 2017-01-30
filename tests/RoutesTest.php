<?php

class RoutesTest extends \TestCase
{
    /** @test */
    public function user_cant_access_json_file_if_it_is_not_generated()
    {
        $jsonUrl = route('l5-swagger.docs');

        $this->get($jsonUrl)
            ->isNotFound();
    }

    /** @test */
    public function user_can_access_json_file_if_it_is_generated()
    {
        $jsonUrl = route('l5-swagger.docs');

        $this->crateJsonDocumentationFile();

        $this->get($jsonUrl)
            ->assertSee('{}')
            ->isOk();
    }

    /** @test */
    public function user_can_access_and_generate_custom_json_file()
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
    public function user_can_access_documentation_interface()
    {
        $this->get(config('l5-swagger.routes.api'))
            ->assertSee(route('l5-swagger.docs', config('l5-swagger.paths.docs_json', 'api-docs.json')))
            ->isOk();
    }
}

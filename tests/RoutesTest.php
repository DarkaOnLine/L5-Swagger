<?php

class RoutesTest extends \TestCase
{
    /** @test */
    public function user_cant_access_json_file_if_it_is_not_generated()
    {
        $jsonUrl = route('l5-swagger.docs');

        //If PHP >= 5.6, laravel 5.3 will throw an Illuminate\Foundation\Testing\HttpException.
        if (version_compare(PHP_VERSION, '5.6', '>=')) {
            $this->setExpectedException(Illuminate\Foundation\Testing\HttpException::class);
        } else {
            $this->setExpectedException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        }

        $this->visit($jsonUrl);
    }

    /** @test */
    public function user_can_access_json_file_if_it_is_generated()
    {
        $jsonUrl = route('l5-swagger.docs');

        $this->crateJsonDocumentationFile();

        $this->visit($jsonUrl)
            ->see('{}')
            ->assertResponseOk();
    }

    /** @test */
    public function user_can_access_and_generate_custom_json_file()
    {
        $customJsonFileName = 'docs.v1.json';

        $jsonUrl = route('l5-swagger.docs', $customJsonFileName);

        $this->setCustomDocsFileName($customJsonFileName);
        $this->crateJsonDocumentationFile();

        $this->visit($jsonUrl)
            ->see('{}')
            ->assertResponseOk();
    }

    /** @test */
    public function user_can_access_documentation_interface()
    {
        $this->visit(config('l5-swagger.routes.api'))
            ->see(route('l5-swagger.docs', config('l5-swagger.paths.docs_json', 'api-docs.json')))
            ->assertResponseOk();
    }
}

<?php

class RoutesTest extends \TestCase
{
    /** @test */
    public function user_cant_access_json_file_if_it_is_not_generated()
    {
        $jsonUrl = config('l5-swagger.routes.docs');
        $this->setExpectedException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->visit($jsonUrl);
    }

    /** @test */
    public function user_can_access_json_file_if_it_is_generated()
    {
        $jsonUrl = config('l5-swagger.routes.docs');

        $this->crateJsonDocumentationFile();

        $this->visit($jsonUrl)
            ->see('{}')
            ->assertResponseOk();
    }

    /** @test */
    public function user_can_access_documentation_interface()
    {
        $this->get(config('l5-swagger.routes.api'));

        $this->assertResponseOk();
    }
}

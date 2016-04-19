<?php

class GeneratorTest extends \TestCase
{
    /** @test */
    public function can_generate_api_json_file()
    {
        $this->setAnnotationsPath();

        \L5Swagger\Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->visit(route('l5-swagger.docs'))
            ->see('L5 Swagger API')
            ->assertResponseOk();
    }
}

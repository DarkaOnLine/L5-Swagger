<?php

namespace Tests;

use L5Swagger\Exceptions\L5SwaggerException;

class SecurityDefinitionsTest extends TestCase
{
    /**
     * @test
     *
     * @throws L5SwaggerException
     */
    public function canGenerateApiJsonFileWithSecurityDefinitionOpenApi3(): void
    {
        $this->setAnnotationsPath();

        $cfg = config('l5-swagger.documentations.default');
        $security = [
            'new_api_key_securitye' => [
                'type' => 'apiKey',
                'name' => 'api_key_name',
                'in' => 'query',
            ],
        ];
        $cfg['security'] = $security;
        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $cfg,
            ],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->generator->generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.default.docs'))
             ->assertSee('new_api_key_securitye')
             ->assertJsonFragment($security)
             ->isOk();
    }
}

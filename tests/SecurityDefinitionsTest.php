<?php

namespace Tests;

use L5Swagger\Generator;
use L5Swagger\SecurityDefinitions;

class SecurityDefinitionsTest extends TestCase
{
    /** @test */
    public function canGenerateApiJsonFileWithSecurityDefinition()
    {
        $this->setAnnotationsPath(2.0);

        $cfg = config('l5-swagger');
        $security = [
            'new_api_key_securitye' => [
                'type' => 'apiKey',
                'name' => 'api_key_name',
                'in' => 'query',
            ],
        ];
        $cfg['security'] = $security;
        $cfg['swagger_version'] = '2.0';
        config(['l5-swagger' => $cfg]);

        Generator::generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
             ->assertSee('new_api_key_securitye')
             ->assertJsonFragment($security)
             ->isOk();
    }

    /** @test */
    public function canGenerateApiJsonFileWithSecurityDefinitionOpenApi3()
    {
        $this->setAnnotationsPath(3.0);

        $cfg = config('l5-swagger');
        $security = [
            'new_api_key_securitye' => [
                'type' => 'apiKey',
                'name' => 'api_key_name',
                'in' => 'query',
            ],
        ];
        $cfg['security'] = $security;
        $cfg['swagger_version'] = '3.0';
        config(['l5-swagger' => $cfg]);

        Generator::generateDocs();

        var_dump(json_decode(file_get_contents($this->jsonDocsFile())));

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
             ->assertSee('new_api_key_securitye')
             ->assertJsonFragment(['components' => ['securitySchemes' => $security]])
             ->isOk();
    }
}
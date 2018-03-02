<?php

namespace Tests;

use L5Swagger\Generator;

class SecurityDefinitionsTest extends TestCase
{
    /** @test */
    public function canGenerateApiJsonFileWithSecurityDefinition()
    {
        if ($this->isOpenApi()) {
            $this->markTestSkipped('only for openApi 2.0');
        }
        $this->setAnnotationsPath();

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

        tap(new Generator)->generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
             ->assertSee('new_api_key_securitye')
             ->assertJsonFragment($security)
             ->isOk();
    }

    /** @test */
    public function canGenerateApiJsonFileWithSecurityDefinitionOpenApi3()
    {
        if (!$this->isOpenApi()) {
            $this->markTestSkipped('only for openApi 3.0');
        }
        $this->setAnnotationsPath();

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

        tap(new Generator)->generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.docs'))
             ->assertSee('new_api_key_securitye')
             ->assertJsonFragment($security)
             ->isOk();
    }
}

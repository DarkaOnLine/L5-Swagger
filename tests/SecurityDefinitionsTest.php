<?php

namespace Tests;

use L5Swagger\Exceptions\L5SwaggerException;

class SecurityDefinitionsTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideConfigAndSchemes
     *
     * @param  array  $securitySchemes
     * @param  array  $security
     *
     * @throws L5SwaggerException
     */
    public function canGenerateApiJsonFileWithSecurityDefinition(
        array $securitySchemes,
        array $security
    ): void {
        $this->setAnnotationsPath();

        $config = config('l5-swagger.documentations.default');

        $config['securityDefinitions']['securitySchemes'] = $securitySchemes;
        $config['securityDefinitions']['security'] = $security;

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $config,
            ],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $this->generator->generateDocs();

        $this->assertTrue(file_exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.default.docs'))
             ->assertSee('new_api_key_securitye')
             ->assertSee('oauth2')  // From annotations
             ->assertSee('read:projects')
             ->assertSee('read:oauth2') // From annotations
             ->assertJsonFragment($securitySchemes)
             ->assertJsonFragment($security)
             ->isOk();
    }

    /**
     * @return iterable
     */
    public function provideConfigAndSchemes(): iterable
    {
        $securitySchemes = [
            'new_api_key_securitye' => [
                'type' => 'apiKey',
                'name' => 'api_key_name',
                'in' => 'query',
            ],
        ];

        $security = [
            'new_api_key_securitye' => [
                'read:projects',
            ],
        ];

        yield 'default config' => [
            'securitySchemes' => $securitySchemes,
            'security' => $security,
        ];
    }
}

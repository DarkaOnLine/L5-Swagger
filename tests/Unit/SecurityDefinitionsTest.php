<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\SecurityDefinitions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Security definition')]
#[CoversClass(SecurityDefinitions::class)]
class SecurityDefinitionsTest extends TestCase
{
    /**
     * @throws L5SwaggerException
     */
    public function testItWillNotAddEmptySecurityItems(): void
    {
        $fileSystem = new Filesystem();

        $this->setAnnotationsPath();

        $defaultConfig = config('l5-swagger.defaults');
        $defaultConfig['securityDefinitions']['securitySchemes'] = [[]];
        $defaultConfig['securityDefinitions']['security'] = [[]];

        $config = config('l5-swagger.documentations.default');

        $config['securityDefinitions']['securitySchemes'] = [[]];
        $config['securityDefinitions']['security'] = [[]];

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $config,
            ],
            'defaults' => $defaultConfig,
        ]]);

        $this->generator->generateDocs();

        $this->assertTrue($fileSystem->exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.default.docs'))
            ->assertSee('oauth2')  // From annotations
            ->assertSee('read:oauth2') // From annotations
            ->assertJsonMissing(['securitySchemes' => []])
            ->assertJsonMissing(['security' => []])
            ->isOk();
    }

    /**
     * @param  array<string,string>  $securitySchemes
     * @param  array<string,string>  $security
     * @return void
     *
     * @throws L5SwaggerException
     */
    #[DataProvider('provideConfigAndSchemes')]
    public function testCanGenerateApiJsonFileWithSecurityDefinition(
        array $securitySchemes,
        array $security
    ): void {
        $fileSystem = new Filesystem();

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

        $this->assertTrue($fileSystem->exists($this->jsonDocsFile()));

        $this->get(route('l5-swagger.default.docs'))
             ->assertSee('new_api_key_security')
             ->assertSee('oauth2')  // From annotations
             ->assertSee('read:projects')
             ->assertSee('read:oauth2') // From annotations
             ->assertJsonFragment($securitySchemes)
             ->assertJsonFragment($security)
             ->isOk();
    }

    /**
     * @param  array<string,mixed>  $securitySchemes
     * @param  array<string,mixed>  $expected
     *
     * @throws L5SwaggerException
     */
    #[DataProvider('provideOauth2Schemes')]
    public function testItWillGenerateScopesAsMap(array $securitySchemes, array $expected): void
    {
        $this->setAnnotationsPath();

        $config = config('l5-swagger.documentations.default');

        $config['securityDefinitions']['securitySchemes'] = $securitySchemes;

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $config,
            ],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        $json = json_decode($this->get(route('l5-swagger.default.docs'))->getContent(), false);

        foreach ($expected as $name => $scopes) {
            $scheme = $json->components->securitySchemes->{$name};
            $actual = $scheme->flows->password->scopes ?? $scheme->scopes;

            $this->assertEquals($scopes, $actual);
            $this->assertSame(is_object($scopes), is_object($actual));
        }
    }

    public static function provideOauth2Schemes(): \Generator
    {
        yield 'empty scopes in flow' => [
            'securitySchemes' => [
                'password_auth' => [
                    'type' => 'oauth2',
                    'flows' => [
                        'password' => [
                            'tokenUrl' => 'https://example.com/token',
                            'scopes' => [],
                        ],
                    ],
                ],
            ],
            'expected' => ['password_auth' => new \stdClass()],
        ];

        yield 'empty top level scopes' => [
            'securitySchemes' => [
                'legacy_auth' => [
                    'type' => 'oauth2',
                    'flow' => 'password',
                    'tokenUrl' => 'https://example.com/token',
                    'scopes' => [],
                ],
            ],
            'expected' => ['legacy_auth' => new \stdClass()],
        ];

        yield 'non empty scopes in flow' => [
            'securitySchemes' => [
                'password_auth' => [
                    'type' => 'oauth2',
                    'flows' => [
                        'password' => [
                            'tokenUrl' => 'https://example.com/token',
                            'scopes' => ['read' => 'Read access'],
                        ],
                    ],
                ],
            ],
            'expected' => ['password_auth' => (object) ['read' => 'Read access']],
        ];
    }

    public static function provideConfigAndSchemes(): \Generator
    {
        $securitySchemes = [
            'new_api_key_security' => [
                'type' => 'apiKey',
                'name' => 'api_key_name',
                'in' => 'query',
            ],
        ];

        $security = [
            'new_api_key_security' => [
                'read:projects',
            ],
        ];

        yield 'default config' => [
            'securitySchemes' => $securitySchemes,
            'security' => $security,
        ];
    }
}

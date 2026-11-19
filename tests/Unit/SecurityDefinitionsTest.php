<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\Generator;
use L5Swagger\KeepSecuritySchemes;
use L5Swagger\SecurityDefinitions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Security definition')]
#[CoversClass(SecurityDefinitions::class)]
#[CoversClass(KeepSecuritySchemes::class)]
#[CoversClass(Generator::class)]
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
     * @throws L5SwaggerException
     */
    #[DataProvider('provideModes')]
    public function testItKeepsASchemeOnlyTheSecuritySettingReferences(string $mode, bool $keepsUnreferenced): void
    {
        $this->setAnnotationsPath();

        $config = config('l5-swagger.documentations.default');

        // `Api Key` is declared, but referenced from nowhere.
        $config['paths']['annotations'] = [$config['paths']['annotations'], __DIR__.'/../storage/annotations/SecuritySchemes'];
        $config['scanOptions']['mode'] = $mode;
        $config['securityDefinitions']['securitySchemes'] = [];
        $config['securityDefinitions']['security'] = [['Password Based' => []]];

        config(['l5-swagger' => [
            'default' => 'default',
            'documentations' => [
                'default' => $config,
            ],
            'defaults' => config('l5-swagger.defaults'),
        ]]);

        // The generator from setUp() read the config before the changes above.
        $this->makeGenerator();
        $this->generator->generateDocs();

        $documentation = json_decode((new Filesystem())->get($this->jsonDocsFile()), true);

        $this->assertArrayHasKey('Password Based', $documentation['components']['securitySchemes']);
        $this->assertSame([['Password Based' => []]], $documentation['security']);
        // Classic has no cleanup; elsewhere only the schemes the setting names are kept.
        $this->assertSame($keepsUnreferenced, isset($documentation['components']['securitySchemes']['Api Key']));
    }

    public static function provideModes(): \Generator
    {
        yield 'classic' => ['classic', true];
        yield 'hybrid' => ['hybrid', false];
    }

    /**
     * @param  array<int|string,mixed>  $security
     * @param  list<string>  $expected
     */
    #[DataProvider('provideSecuritySettings')]
    public function testItListsTheSchemesTheSecuritySettingReferences(array $security, array $expected): void
    {
        $this->assertSame($expected, (new SecurityDefinitions([], $security))->referencedSchemes());
    }

    public static function provideSecuritySettings(): \Generator
    {
        yield 'requirements' => [
            [['oauth2' => ['read'], 'api_key' => []], ['oauth2' => ['write']]],
            ['oauth2', 'api_key'],
        ];
        yield 'one map' => [['new_api_key_security' => ['read:projects']], ['new_api_key_security']];
        yield 'empty' => [[[]], []];
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

<?php

namespace Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use L5Swagger\Exceptions\L5SwaggerException;

/**
 * @testdox Console commands
 */
class ConsoleTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider provideGenerateCommands
     *
     * @param  string  $artisanCommand
     *
     * @throws L5SwaggerException
     * @throws FileNotFoundException
     */
    public function canGenerate(string $artisanCommand): void
    {
        $fileSystem = new Filesystem();

        $this->setAnnotationsPath();

        Artisan::call($artisanCommand);

        $this->assertFileExists($this->jsonDocsFile());

        $fileContent = $fileSystem->get($this->jsonDocsFile());

        $this->assertJson($fileContent);
        $this->assertStringContainsString('L5 Swagger', $fileContent);
    }

    /**
     * @return iterable
     */
    public static function provideGenerateCommands(): iterable
    {
        yield 'default' => [
            'artisanCommand' => 'l5-swagger:generate',
        ];
        yield 'all' => [
            'artisanCommand' => 'l5-swagger:generate --all',
        ];
    }

    /**
     * @test
     *
     * @throws L5SwaggerException
     */
    public function canPublish(): void
    {
        $fileSystem = new Filesystem();
        Artisan::call('vendor:publish', ['--provider' => 'L5Swagger\L5SwaggerServiceProvider']);

        $config = $this->configFactory->documentationConfig();

        $this->assertTrue($fileSystem->exists(config_path('l5-swagger.php')));
        $this->assertTrue($fileSystem->exists($config['paths']['views'].'/index.blade.php'));
    }
}

<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use L5Swagger\Exceptions\L5SwaggerException;

class ConsoleTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideGenerateCommands
     *
     * @param  string  $artisanCommand
     *
     * @throws L5SwaggerException
     */
    public function canGenerate(string $artisanCommand): void
    {
        $this->setAnnotationsPath();

        Artisan::call($artisanCommand);

        $this->assertFileExists($this->jsonDocsFile());

        $fileContent = file_get_contents($this->jsonDocsFile());

        $this->assertJson($fileContent);
        $this->assertStringContainsString('L5 Swagger', $fileContent);
    }

    /**
     * @return iterable
     */
    public function provideGenerateCommands(): iterable
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
        Artisan::call('vendor:publish', ['--provider' => 'L5Swagger\L5SwaggerServiceProvider']);

        $config = $this->configFactory->documentationConfig();

        $this->assertTrue(file_exists(config_path('l5-swagger.php')));
        $this->assertTrue(file_exists($config['paths']['views'].'/index.blade.php'));
    }
}

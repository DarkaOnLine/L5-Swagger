<?php

namespace Tests\Unit;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use L5Swagger\Console\GenerateDocsCommand;
use L5Swagger\Exceptions\L5SwaggerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Console commands')]
#[CoversClass(GenerateDocsCommand::class)]
class ConsoleTest extends TestCase
{
    /**
     * @throws L5SwaggerException
     * @throws FileNotFoundException
     */
    #[DataProvider('provideGenerateCommands')]
    public function testCanGenerate(string $artisanCommand): void
    {
        $fileSystem = new Filesystem();

        $this->setAnnotationsPath();

        Artisan::call($artisanCommand);

        $this->assertFileExists($this->jsonDocsFile());

        $fileContent = $fileSystem->get($this->jsonDocsFile());

        $this->assertJson($fileContent);
        $this->assertStringContainsString('L5 Swagger', $fileContent);
    }

    public static function provideGenerateCommands(): \Generator
    {
        yield 'default' => [
            'artisanCommand' => 'l5-swagger:generate',
        ];
        yield 'all' => [
            'artisanCommand' => 'l5-swagger:generate --all',
        ];
    }

    /**
     * @throws L5SwaggerException
     */
    public function testCanPublish(): void
    {
        $fileSystem = new Filesystem();
        Artisan::call('vendor:publish', ['--provider' => 'L5Swagger\L5SwaggerServiceProvider']);

        $config = $this->configFactory->documentationConfig();

        $this->assertTrue($fileSystem->exists(config_path('l5-swagger.php')));
        $this->assertTrue($fileSystem->exists($config['paths']['views'].'/index.blade.php'));
    }
}

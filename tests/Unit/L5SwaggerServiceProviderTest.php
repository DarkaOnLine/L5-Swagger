<?php

namespace Tests\Unit;

use Illuminate\Foundation\Application;
use L5Swagger\Console\GenerateDocsCommand;
use L5Swagger\L5SwaggerServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('L5SwaggerServiceProvider')]
#[CoversClass(L5SwaggerServiceProvider::class)]
class L5SwaggerServiceProviderTest extends TestCase
{
    public function testItRegistersGenerateCommandAsSingleton(): void
    {
        if (! $this->app instanceof Application) {
            throw new \RuntimeException('Application is not set');
        }

        $app = $this->app;
        $command = $app->make('command.l5-swagger.generate');

        $this->assertInstanceOf(GenerateDocsCommand::class, $command);
        $this->assertSame($command, $app->make('command.l5-swagger.generate'));
    }
}

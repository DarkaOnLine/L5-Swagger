<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;

class ConsoleTest extends TestCase
{
    /** @test */
    public function canGenerate()
    {
        $this->setAnnotationsPath();

        Artisan::call('l5-swagger:generate');

        $this->assertFileExists($this->jsonDocsFile());

        $fileContent = file_get_contents($this->jsonDocsFile());

        $this->assertJson($fileContent);
        $this->assertContains('L5 Swagger', $fileContent);
    }

    /** @test */
    public function canPublish()
    {
        Artisan::call('vendor:publish', ['--provider' => 'L5Swagger\L5SwaggerServiceProvider']);

        $this->assertTrue(file_exists(config_path('l5-swagger.php')));
        $this->assertTrue(file_exists(config('l5-swagger.paths.views').'/index.blade.php'));
    }
}

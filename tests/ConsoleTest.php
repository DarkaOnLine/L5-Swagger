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
        $this->assertContains('L5 Swagger API', $fileContent);
    }

    /** @test */
    public function canPublish()
    {
        $this->setAnnotationsPath();

        Artisan::call('l5-swagger:publish');

        $this->assertTrue(file_exists(config_path('l5-swagger.php')));
        $this->assertTrue(file_exists(config('l5-swagger.paths.views').'/index.blade.php'));
    }

    /** @test */
    public function canPublishConfig()
    {
        $this->setAnnotationsPath();

        Artisan::call('l5-swagger:publish-config');

        $this->assertTrue(file_exists(config_path('l5-swagger.php')));
    }

    /** @test */
    public function canPublishViews()
    {
        $this->setAnnotationsPath();

        Artisan::call('l5-swagger:publish-views');

        $this->assertTrue(file_exists(config('l5-swagger.paths.views').'/index.blade.php'));
    }
}

<?php

use Illuminate\Support\Facades\Artisan;

class ConsoleTest extends \TestCase
{
    /** @test */
    public function can_generate()
    {
        $this->setAnnotationsPath();

        Artisan::call('l5-swagger:generate');

        $this->assertFileExists($this->jsonDocsFile());

        $fileContent = file_get_contents($this->jsonDocsFile());

        $this->assertJson($fileContent);
        $this->assertContains('L5 Swagger API', $fileContent);
    }

    /** @test */
    public function can_publish()
    {
        $this->setAnnotationsPath();

        Artisan::call('l5-swagger:publish');

        $this->assertTrue(file_exists(config_path('l5-swagger.php')));
        $this->assertTrue(file_exists(config('l5-swagger.paths.views').'/index.blade.php'));
    }
}

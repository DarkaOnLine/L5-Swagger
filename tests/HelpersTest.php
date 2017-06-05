<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;

class HelpersTest extends TestCase
{
    /** @test */
    public function assetFunctionThrowsExceptionIfFileDoesNotExsist()
    {
        $this->expectException(\L5Swagger\Exceptions\L5SwaggerException::class);
        l5_swagger_asset('asdasd');
    }
}

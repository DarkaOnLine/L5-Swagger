<?php

namespace Tests;

use L5Swagger\Exceptions\L5SwaggerException;

class HelpersTest extends TestCase
{
    /** @test */
    public function assetFunctionThrowsExceptionIfFileDoesNotExists(): void
    {
        $this->expectException(L5SwaggerException::class);

        l5_swagger_asset('default', 'asdasd');
    }
}

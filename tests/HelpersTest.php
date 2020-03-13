<?php

namespace Tests;

class HelpersTest extends TestCase
{
    /** @test */
    public function assetFunctionThrowsExceptionIfFileDoesNotExists(): void
    {
        $this->expectException(\L5Swagger\Exceptions\L5SwaggerException::class);

        l5_swagger_asset('default', 'asdasd');
    }
}

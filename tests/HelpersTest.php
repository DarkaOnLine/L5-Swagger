<?php

namespace Tests;

use L5Swagger\Exceptions\L5SwaggerException;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Helpers')]
class HelpersTest extends TestCase
{
    public function testAssetFunctionThrowsExceptionIfFileDoesNotExists(): void
    {
        $this->expectException(L5SwaggerException::class);

        l5_swagger_asset('default', 'asdasd');
    }
}

<?php

namespace Tests;

use L5Swagger\Exceptions\L5SwaggerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * @covers L5SwaggerException
 */
#[TestDox('Helpers')]
#[CoversClass(L5SwaggerException::class)]
#[CoversFunction('l5_swagger_asset')]
#[CoversFunction('swagger_ui_dist_path')]
class HelpersTest extends TestCase
{
    public function testAssetFunctionThrowsExceptionIfFileDoesNotExists(): void
    {
        $this->expectException(L5SwaggerException::class);

        l5_swagger_asset('default', 'asdasd');
    }
}

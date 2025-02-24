<?php

namespace Tests\Unit;

use L5Swagger\Exceptions\L5SwaggerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Helpers')]
#[CoversClass(L5SwaggerException::class)]
#[CoversFunction('l5_swagger_asset')]
#[CoversFunction('swagger_ui_dist_path')]
class HelpersTest extends TestCase
{
    public function testAssetFunctionReturnsRoute(): void
    {
        $path = l5_swagger_asset('default', 'swagger-ui.js');

        $this->assertStringContainsString('http://localhost/docs/asset/swagger-ui.js?v=', $path);
    }

    /**
     * @throws L5SwaggerException
     */
    public function testAssetFunctionThrowsExceptionIfFileNotFound(): void
    {
        $this->expectException(L5SwaggerException::class);
        $this->expectExceptionMessage('Requested L5 Swagger asset file (swagger-ui.css) does not exists');

        $this->deleteAssets();

        l5_swagger_asset('default', 'swagger-ui.css');
    }

    /**
     * @throws L5SwaggerException
     */
    public function testGeneratesBasePathForAssetsThrowsExceptionIfFileIsNotAllowed(): void
    {
        $this->expectException(L5SwaggerException::class);

        swagger_ui_dist_path('default', 'foo.bar');
    }

    /**
     * @throws L5SwaggerException
     */
    public function testGeneratesBasePathForAssets(): void
    {
        $path = swagger_ui_dist_path('default');

        $this->assertStringContainsString('swagger-api/swagger-ui/dist', $path);
    }

    /**
     * @throws L5SwaggerException
     */
    public function testGeneratesAssetPathForAssets(): void
    {
        $path = swagger_ui_dist_path('default', 'swagger-ui.js');

        $this->assertStringContainsString('swagger-api/swagger-ui/dist/swagger-ui.js', $path);
    }
}

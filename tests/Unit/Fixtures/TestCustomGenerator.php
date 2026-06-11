<?php

namespace Tests\Unit\Fixtures;

use L5Swagger\CustomGeneratorInterface;
use OpenApi\Generator as OpenApiGenerator;

class TestCustomGenerator implements CustomGeneratorInterface
{
    public function create(): OpenApiGenerator
    {
        return new OpenApiGenerator();
    }
}

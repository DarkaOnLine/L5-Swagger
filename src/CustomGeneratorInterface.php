<?php

namespace L5Swagger;

use OpenApi\Generator as OpenApiGenerator;

interface CustomGeneratorInterface
{
    public function create(): OpenApiGenerator;
}

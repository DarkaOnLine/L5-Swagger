<?php

namespace L5Swagger;

use OpenApi\Builder;

interface CustomBuilderInterface
{
    public function create(): Builder;
}

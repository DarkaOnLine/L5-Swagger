<?php

namespace Tests\Unit\Fixtures;

use L5Swagger\CustomBuilderInterface;
use OpenApi\Builder;

class TestCustomBuilder implements CustomBuilderInterface
{
    public function create(): Builder
    {
        return new Builder();
    }
}

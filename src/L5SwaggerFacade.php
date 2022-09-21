<?php

namespace L5Swagger;

use Illuminate\Support\Facades\Facade;

class L5SwaggerFacade extends Facade
{
    /**
     * @codeCoverageIgnore
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return Generator::class;
    }
}

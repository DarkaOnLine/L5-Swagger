<?php

namespace L5Swagger;

use Illuminate\Support\Facades\Facade;

class L5SwaggerFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return Generator::class;
    }
}

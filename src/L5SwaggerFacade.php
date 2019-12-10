<?php

namespace L5Swagger;

use Illuminate\Support\Facades\Facade;

class L5SwaggerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Generator::class;
    }
}

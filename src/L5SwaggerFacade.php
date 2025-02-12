<?php

namespace L5Swagger;

use Illuminate\Support\Facades\Facade;

class L5SwaggerFacade extends Facade
{
    /**
     * Get the registered name of the component being accessed through the facade.
     *
     * @codeCoverageIgnore
     *
     * @return string The name or class of the underlying component.
     */
    protected static function getFacadeAccessor(): string
    {
        return Generator::class;
    }
}

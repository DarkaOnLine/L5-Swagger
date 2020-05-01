<?php

namespace L5Swagger\Http\Middleware;

use Closure;
use L5Swagger\ConfigFactory;

class Config
{
    private $configFactory;

    public function __construct(ConfigFactory $configFactory)
    {
        $this->configFactory = $configFactory;
    }

    public function handle($request, Closure $next)
    {
        $actions = $request->route()->getAction();

        $documentation = $actions['l5-swagger.documentation'];

        $config = $this->configFactory->documentationConfig($documentation);

        $request->offsetSet('documentation', $documentation);
        $request->offsetSet('config', $config);

        return $next($request);
    }
}

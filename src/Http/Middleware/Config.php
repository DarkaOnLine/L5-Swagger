<?php

namespace L5Swagger\Http\Middleware;

use Closure;
use L5Swagger\ConfigFactory;
use L5Swagger\Exceptions\L5SwaggerException;

class Config
{
    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * Config constructor.
     *
     * @param  ConfigFactory  $configFactory
     */
    public function __construct(ConfigFactory $configFactory)
    {
        $this->configFactory = $configFactory;
    }

    /**
     * @param  $request
     * @param  Closure  $next
     * @return mixed
     *
     * @throws L5SwaggerException
     */
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

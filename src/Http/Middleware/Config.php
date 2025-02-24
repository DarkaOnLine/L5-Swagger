<?php

namespace L5Swagger\Http\Middleware;

use Closure;
use L5Swagger\ConfigFactory;
use L5Swagger\Exceptions\L5SwaggerException;

class Config
{
    public function __construct(
        private readonly  ConfigFactory $configFactory
    ) {
    }

    /**
     * Handles the incoming request by extracting documentation configuration and setting it on the request.
     *
     * @param  mixed  $request  The incoming HTTP request.
     * @param  Closure  $next  The next middleware in the pipeline.
     * @return mixed The processed HTTP response after passing through the next middleware.
     *
     * @throws L5SwaggerException
     */
    public function handle(mixed $request, Closure $next): mixed
    {
        $actions = $request->route()->getAction();

        $documentation = $actions['l5-swagger.documentation'];

        $config = $this->configFactory->documentationConfig($documentation);

        $request->offsetSet('documentation', $documentation);
        $request->offsetSet('config', $config);

        return $next($request);
    }
}

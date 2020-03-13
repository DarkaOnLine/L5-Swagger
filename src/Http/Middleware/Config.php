<?php

namespace L5Swagger\Http\Middleware;

use Closure;

class Config
{
    public function handle($request, Closure $next)
    {
        $actions = $request->route()->getAction();

        $documentation = $actions['l5-swagger.documentation'];

        $config = config('l5-swagger.documentations.'.$documentation);

        $request->offsetSet('documentation', $documentation);
        $request->offsetSet('config', $config);

        return $next($request);
    }
}

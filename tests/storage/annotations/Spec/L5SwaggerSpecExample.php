<?php

namespace Tests\storage\annotations\Spec;

use OpenApi\Spec as OA;

#[OA\Info(title: 'L5 Spec', version: '1.0.0')]
#[OA\Server(url: L5_SWAGGER_CONST_HOST)]
class L5SwaggerSpecExample
{
    #[OA\Operation\Get(path: '/projects', operationId: 'listProjects')]
    #[OA\Response(response: 200, description: 'The projects')]
    public function list(): void
    {
    }

    #[OA\Operation\Get(path: '/clients', operationId: 'listClients')]
    #[OA\Response(response: 200, description: 'The clients')]
    public function clients(): void
    {
    }
}

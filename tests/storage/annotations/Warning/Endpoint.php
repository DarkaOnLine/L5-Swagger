<?php

namespace Tests\storage\annotations\Warning;

use OpenApi\Attributes as OA;

#[OA\Info(version: "1.0.0", title: "Warning Test")]
class Endpoint
{
    #[OA\Get(path: "/ping", operationId: "ping")]
    public function ping(): void
    {
    }
}

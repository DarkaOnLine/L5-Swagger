<?php

namespace Tests\storage\annotations\OpenApiNoServer;

use OpenApi\Attributes as OA;

class Endpoint
{
    #[OA\Get(
        path: "/ping",
        operationId: "ping",
        summary: "Health check",
        responses: [
            new OA\Response(response: 200, description: "OK")
        ]
    )]
    public function ping(): void
    {
    }
}

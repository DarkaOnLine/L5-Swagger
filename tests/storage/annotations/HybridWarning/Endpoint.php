<?php

namespace Tests\storage\annotations\HybridWarning;

use OpenApi\Attributes as OA;

#[OA\Info(version: "1.0.0", title: "Hybrid Warning Test")]
class Endpoint
{
    #[OA\Get(path: "/kind", operationId: "kind", responses: [
        new OA\Response(response: 200, description: "The kind", content: new OA\JsonContent(type: "string", const: "pet")),
    ])]
    public function kind(): void
    {
    }
}

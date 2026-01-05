<?php

namespace Tests\storage\annotations\OpenApi\Clients;

use OpenApi\Attributes as OA;

class L5SwaggerAnnotationsExampleClients
{
    /**
     * Returns list of clients
     */
    #[OA\Get(
        path: "/clients",
        operationId: "getClientsList",
        tags: ["Clients"],
        summary: "Get list of clients",
        description: "Returns list of clients",
        responses: [
            new OA\Response(
                response: 200,
                description: "successful operation"
            )
        ]
    )]
    public function getClientsList()
    {
    }
}

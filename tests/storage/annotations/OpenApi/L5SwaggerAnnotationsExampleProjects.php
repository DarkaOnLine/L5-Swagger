<?php

namespace Tests\storage\annotations\OpenApi;

use OpenApi\Attributes as OA;

class L5SwaggerAnnotationsExampleProjects
{
    /**
     * Returns list of projects
     */
    #[OA\Get(
        path: "/projects",
        operationId: "getProjectsList",
        tags: ["Projects"],
        summary: "Get list of projects",
        description: "Returns list of projects",
        responses: [
            new OA\Response(
                response: 200,
                description: "successful operation"
            ),
            new OA\Response(response: 400, description: "Bad request")
        ],
        security: [
            ["api_key_security_example" => []]
        ]
    )]
    public function getProjectsList()
    {
    }

    /**
     * Get project information
     */
    #[OA\Get(
        path: "/projects/{id}",
        operationId: "getProjectById",
        tags: ["Projects"],
        summary: "Get project information",
        description: "Returns project data",
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Project id",
                required: true,
                in: "path",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "successful operation"
            ),
            new OA\Response(response: 400, description: "Bad request"),
            new OA\Response(response: 404, description: "Resource Not Found")
        ],
        security: [
            [
                "oauth2_security_example" => ["write:projects", "read:projects"]
            ]
        ]
    )]
    public function getProjectById()
    {
    }
}

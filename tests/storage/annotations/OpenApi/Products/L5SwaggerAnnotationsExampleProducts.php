<?php

namespace Tests\storage\annotations\OpenApi\Products;

use OpenApi\Attributes as OA;

class L5SwaggerAnnotationsExampleProducts
{
    /**
     * Returns list of products
     */
    #[OA\Post(
        path: "/products",
        tags: ["Products"],
        summary: "Get list of products",
        description: "Returns list of products",
        responses: [
            new OA\Response(
                response: 200,
                description: "successful operation"
            )
        ]
    )]
    public function getProductsList()
    {
    }
}

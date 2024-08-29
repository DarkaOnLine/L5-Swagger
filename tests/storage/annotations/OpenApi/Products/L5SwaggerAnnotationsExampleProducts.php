<?php

namespace Tests\storage\annotations\OpenApi\Products;

class L5SwaggerAnnotationsExampleProducts
{
    /**
     * @OA\Post(
     *      path="/products",
     *      tags={"Products"},
     *      summary="Get list of products",
     *      description="Returns list of products",
     *      @OA\Response(
     *          response=200,
     *          description="successful operation"
     *       )
     *     )
     *
     * Returns list of products
     */
    public function getProductsList()
    {
    }
}

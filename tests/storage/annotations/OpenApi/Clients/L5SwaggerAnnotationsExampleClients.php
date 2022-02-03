<?php

namespace Tests\storage\annotations\OpenApi\Clients;

class L5SwaggerAnnotationsExampleClients
{
    /**
     * @OA\Get(
     *      path="/clients",
     *      operationId="getClientsList",
     *      tags={"Clients"},
     *      summary="Get list of clients",
     *      description="Returns list of clients",
     *      @OA\Response(
     *          response=200,
     *          description="successful operation"
     *       )
     *     )
     *
     * Returns list of clients
     */
    public function getClientsList()
    {
    }
}

<?php

namespace Tests\storage\annotations\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Server(
    url: "http://my-default-host.com",
    description: "L5 Swagger OpenApi dynamic host server"
)]
#[OA\Server(
    url: "https://projects.dev/api/v1",
    description: "L5 Swagger OpenApi Server"
)]
class L5SwaggerAnnotationsExampleServer
{
}

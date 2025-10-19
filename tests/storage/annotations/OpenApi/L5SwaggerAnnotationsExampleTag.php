<?php

namespace Tests\storage\annotations\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "project",
    description: "Everything about your Projects",
    externalDocs: new OA\ExternalDocumentation(
        description: "Find out more",
        url: "https://swagger.io"
    )
)]
#[OA\Tag(
    name: "user",
    description: "Operations about user",
    externalDocs: new OA\ExternalDocumentation(
        description: "Find out more about",
        url: "https://swagger.io"
    )
)]
#[OA\ExternalDocumentation(
    description: "Find out more about Swagger and OpenApi",
    url: "https://swagger.io"
)]
class L5SwaggerAnnotationsExampleTag
{
}

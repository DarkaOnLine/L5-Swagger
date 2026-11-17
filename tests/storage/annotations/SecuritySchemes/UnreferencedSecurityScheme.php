<?php

namespace Tests\storage\annotations\SecuritySchemes;

use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    type: "apiKey",
    name: "X-Api-Key",
    in: "header",
    securityScheme: "Api Key"
)]
class UnreferencedSecurityScheme
{
}

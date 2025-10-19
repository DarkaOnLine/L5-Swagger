<?php

namespace Tests\storage\annotations\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    security: [
        [
            "oauth2" => ["read:oauth2"]
        ]
    ]
)]
class L5SwaggerAnnotationsExampleSecurity
{
}

<?php

namespace Tests\storage\annotations\OpenApiNoServer;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "No Server Test",
    description: "Fixture without Server annotation"
)]
class Info
{
}

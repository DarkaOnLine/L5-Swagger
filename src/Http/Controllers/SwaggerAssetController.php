<?php

namespace L5Swagger\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class SwaggerAssetController extends BaseController
{
    public function index($asset)
    {
        $path = swagger_ui_dist_path($asset);

        return (new Response(
            file_get_contents($path), 200, [
                'Content-Type' => (pathinfo($asset))['extension'] == 'css' ?
                    'text/css' : 'application/javascript',
            ]
        ))->setSharedMaxAge(31536000)
        ->setMaxAge(31536000)
        ->setExpires(new \DateTime('+1 year'));
    }
}

<?php

namespace L5Swagger\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use L5Swagger\Exceptions\L5SwaggerException;

class SwaggerAssetController extends BaseController
{
    public function index(Request $request, $asset)
    {
        $documentation = $request->offsetGet('documentation');

        try {
            $path = swagger_ui_dist_path($documentation, $asset);

            return (new Response(
                file_get_contents($path),
                200,
                [
                    'Content-Type' => (pathinfo($asset))['extension'] == 'css'
                        ? 'text/css'
                        : 'application/javascript',
                ]
            ))->setSharedMaxAge(31536000)
                ->setMaxAge(31536000)
                ->setExpires(new \DateTime('+1 year'));
        } catch (L5SwaggerException $exception) {
            return abort(404, $exception->getMessage());
        }
    }
}

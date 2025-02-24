<?php

namespace L5Swagger\Http\Controllers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use L5Swagger\Exceptions\L5SwaggerException;

/**
 * Handles requests for serving Swagger UI assets.
 */
class SwaggerAssetController extends BaseController
{
    /**
     * Serves a specific documentation asset for the Swagger UI interface.
     *
     * @param  Request  $request  The incoming HTTP request, which includes parameters to locate the requested asset.
     * @return Response The HTTP response containing the requested asset content
     *                  or a 404 error if the asset is not found.
     *
     * @throws FileNotFoundException
     */
    public function index(Request $request): Response
    {
        $fileSystem = new Filesystem();
        $documentation = $request->offsetGet('documentation');
        $asset = $request->offsetGet('asset');

        try {
            $path = swagger_ui_dist_path($documentation, $asset);

            return (new Response(
                $fileSystem->get($path),
                200,
                [
                    'Content-Type' => (isset(pathinfo($asset)['extension']) && pathinfo($asset)['extension'] === 'css')
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

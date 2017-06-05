<?php

namespace L5Swagger\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

class SwaggerAssetController extends BaseController
{
    public function index($asset)
    {
        $path = swagger_ui_dist_path($asset);

        return file_get_contents($path);
    }
}

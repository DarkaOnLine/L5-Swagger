<?php

use L5Swagger\Exceptions\L5SwaggerException;

if (! function_exists('swagger_ui_dist_path')) {
    /**
     * Returns swagger-ui composer dist path.
     *
     * @return string
     */
    function swagger_ui_dist_path($asset = null)
    {
        $allowed_files = [
            'favicon-16x16.png',
            'swagger-ui-standalone-preset.js',
            'favicon-32x32.png',
            'swagger-ui-bundle.js',
            'swagger-ui.js',
            'swagger-ui.css'
        ];

        $path = base_path('vendor/swagger-api/swagger-ui/dist/');

        if (!$asset) {
            return realpath($path);
        }

        if (!in_array($asset, $allowed_files)) {
            throw new L5SwaggerException(sprintf("(%s) - this L5 Swagger asset is not allowed", $asset));
        }

        return realpath($path.$asset);
    }
}

if (! function_exists('l5_swagger_asset')) {
    /**
     * Returns asset from swagger-ui composer package.
     *
     * @param $asset string
     * @return string
     */
    function l5_swagger_asset($asset)
    {
        $file = swagger_ui_dist_path($asset);

        if (!file_exists($file)) {
            throw new L5SwaggerException(sprintf("Requested L5 Swagger asset file (%s) does not exists", $asset));
        }

        return route('l5-swagger.asset', $asset);
    }
}

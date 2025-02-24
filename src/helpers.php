<?php

use L5Swagger\Exceptions\L5SwaggerException;

if (! function_exists('swagger_ui_dist_path')) {
    /**
     * Returns swagger-ui composer dist path.
     *
     * @param  string  $documentation
     * @param  string|null  $asset
     * @return string
     *
     * @throws L5SwaggerException
     */
    function swagger_ui_dist_path(string $documentation, ?string $asset = null): string
    {
        $allowedFiles = [
            'favicon-16x16.png',
            'favicon-32x32.png',
            'oauth2-redirect.html',
            'swagger-ui-bundle.js',
            'swagger-ui-bundle.js.map',
            'swagger-ui-standalone-preset.js',
            'swagger-ui-standalone-preset.js.map',
            'swagger-ui.css',
            'swagger-ui.css.map',
            'swagger-ui.js',
            'swagger-ui.js.map',
        ];

        $defaultPath = 'vendor/swagger-api/swagger-ui/dist/';
        $path = base_path(
            config('l5-swagger.documentations.'.$documentation.'.paths.swagger_ui_assets_path', $defaultPath)
        );

        if (! $asset) {
            return realpath($path) ?: '';
        }

        if (! in_array($asset, $allowedFiles, true)) {
            throw new L5SwaggerException(sprintf('(%s) - this L5 Swagger asset is not allowed', $asset));
        }

        return realpath($path.$asset) ?: '';
    }
}

if (! function_exists('l5_swagger_asset')) {
    /**
     * Returns asset from swagger-ui composer package.
     *
     * @param  string  $documentation
     * @param  $asset  string
     * @return string
     *
     * @throws L5SwaggerException
     */
    function l5_swagger_asset(string $documentation, string $asset): string
    {
        $file = swagger_ui_dist_path($documentation, $asset);

        if (! file_exists($file)) {
            throw new L5SwaggerException(sprintf('Requested L5 Swagger asset file (%s) does not exists', $asset));
        }

        $useAbsolutePath = config('l5-swagger.documentations.'.$documentation.'.paths.use_absolute_path', true);

        return route('l5-swagger.'.$documentation.'.asset', $asset, $useAbsolutePath).'?v='.md5_file($file);
    }
}

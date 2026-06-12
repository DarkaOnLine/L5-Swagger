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
            'swagger-ui-standalone-preset.js',
            'swagger-ui.css',
            'swagger-ui.js',
        ];

        $defaultPath = 'vendor/swagger-api/swagger-ui/dist/';
        $path = base_path(
            config('l5-swagger.documentations.'.$documentation.'.paths.swagger_ui_assets_path', $defaultPath)
        );

        if (! $asset) {
            $resolved = realpath($path);

            if ($resolved === false) {
                throw new L5SwaggerException(
                    sprintf('Swagger UI assets directory not found at: "%s"', e($path))
                );
            }

            return $resolved;
        }

        if (! in_array($asset, $allowedFiles, true)) {
            throw new L5SwaggerException(sprintf('(%s) - this L5 Swagger asset is not allowed', $asset));
        }

        $fullPath = $path.$asset;

        if (! file_exists($fullPath)) {
            throw new L5SwaggerException(
                sprintf('Swagger UI asset not found at: "%s"', e($fullPath))
            );
        }

        return $fullPath;
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

        $useAbsolutePath = config('l5-swagger.documentations.'.$documentation.'.paths.use_absolute_path', true);

        return route('l5-swagger.'.$documentation.'.asset', $asset, $useAbsolutePath).'?v='.md5_file($file);
    }
}

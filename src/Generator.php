<?php

namespace L5Swagger;

use File;
use Config;
use Swagger\Annotations\Server;

class Generator
{
    public static function generateDocs()
    {
        $appDir = config('l5-swagger.paths.annotations');
        $docDir = config('l5-swagger.paths.docs');
        if (! File::exists($docDir) || is_writable($docDir)) {
            // delete all existing documentation
            if (File::exists($docDir)) {
                File::deleteDirectory($docDir);
            }

            self::defineConstants(config('l5-swagger.constants') ?: []);

            File::makeDirectory($docDir);
            $excludeDirs = config('l5-swagger.paths.excludes');
            $swagger = \Swagger\scan($appDir, ['exclude' => $excludeDirs]);

            if (config('l5-swagger.paths.base') !== null) {
                if (config('swagger_version') === '3.0') {
                    $swagger->servers = [
                        new Server(['url' => config('l5-swagger.paths.base')]),
                    ];
                } else {
                    $swagger->basePath = config('l5-swagger.paths.base');
                }
            }

            $filename = $docDir.'/'.config('l5-swagger.paths.docs_json', 'api-docs.json');
            $swagger->saveAs($filename);

            $security = new SecurityDefinitions();
            $security->generate($filename);
        }
    }

    protected static function defineConstants(array $constants)
    {
        if (! empty($constants)) {
            foreach ($constants as $key => $value) {
                defined($key) || define($key, $value);
            }
        }
    }
}

<?php

namespace L5Swagger;

use File;
use Config;

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
                $swagger->basePath = config('l5-swagger.paths.base');
            }

            $filename = $docDir.'/'.config('l5-swagger.paths.docs_json', 'api-docs.json');
            $swagger->saveAs($filename);

            self::appendSecurityDefinisions($filename);
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

    protected static function appendSecurityDefinisions(string $filename)
    {
        $securityConfig = config('l5-swagger.security', []);

        if (is_array($securityConfig) && ! empty($securityConfig)) {
            $documentation = collect(
                json_decode(file_get_contents($filename))
            );

            $securityDefinitions = $documentation->has('securityDefinitions') ? collect($documentation->get('securityDefinitions')) : collect();

            foreach ($securityConfig as $key => $cfg) {
                $securityDefinitions->offsetSet($key, self::arrayToObject($cfg));
            }

            $documentation->offsetSet('securityDefinitions', $securityDefinitions);

            file_put_contents($filename, $documentation->toJson());
        }
    }

    public static function arrayToObject($array)
    {
        return json_decode(json_encode($array));
    }
}

<?php

namespace L5Swagger;

use File;
use Symfony\Component\Yaml\Dumper as YamlDumper;

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

            self::generateServers($swagger);

            $docsJsonFile = $docDir.'/'.config('l5-swagger.paths.docs_json', 'api-docs.json');
            $swagger->saveAs($docsJsonFile);

            if (config('l5-swagger.generate_yaml_copy', false)) {
                file_put_contents(
                    $docDir.'/'.config('l5-swagger.paths.docs_yaml', 'api-docs.yaml'),
                    (new YamlDumper(2))->dump(json_decode(file_get_contents($docsJsonFile), true), 20)
                );
            }

            $security = new SecurityDefinitions();
            $security->generate($docsJsonFile);
        }
    }

    /**
     * Generate servers section or basePath depending on Swagger version.
     */
    protected static function generateServers($swagger)
    {
        if (config('l5-swagger.paths.base') !== null) {
            $isVersion3 = version_compare(config('l5-swagger.swagger_version'), '3.0', '>=');

            if ($isVersion3) {
                $swagger->servers = [
                    new \Swagger\Annotations\Server(['url' => config('l5-swagger.paths.base')]),
                ];
            }

            if (! $isVersion3) {
                $swagger->basePath = config('l5-swagger.paths.base');
            }
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

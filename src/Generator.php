<?php namespace Darkaonline\L5Swagger;

use Swagger\Swagger;
use Config;
use File;

class Generator {
    public static function generateDocs(){
        $appDir = base_path()."/".Config::get('l5-swagger.app-dir');
        $docDir = Config::get('l5-swagger.doc-dir');
        if (!File::exists($docDir) || is_writable($docDir)) {
            // delete all existing documentation
            if (File::exists($docDir)) {
                File::deleteDirectory($docDir);
            }
            File::makeDirectory($docDir);
            $defaultBasePath = Config::get('l5-swagger.default-base-path');
            $defaultApiVersion = Config::get('l5-swagger.default-api-version');
            $defaultSwaggerVersion = Config::get('l5-swagger.default-swagger-version');
            $excludeDirs = Config::get('l5-swagger.excludes');
            $swagger = new Swagger($appDir, $excludeDirs);
            $resourceList = $swagger->getResourceList(array(
                'output' => 'array',
                'apiVersion' => $defaultApiVersion,
                'swaggerVersion' => $defaultSwaggerVersion,
            ));
            $resourceOptions = array(
                'output' => 'json',
                'defaultSwaggerVersion' => $resourceList['swaggerVersion'],
                'defaultBasePath' => $defaultBasePath
            );
            $output = array();
            foreach ($swagger->getResourceNames() as $resourceName) {
                $json = $swagger->getResource($resourceName, $resourceOptions);
                $resourceName = str_replace(DIRECTORY_SEPARATOR, '-', ltrim($resourceName, DIRECTORY_SEPARATOR));
                $output[$resourceName] = $json;
            }
            $filename = $docDir . '/api-docs.json';
            file_put_contents($filename, Swagger::jsonEncode($resourceList, true));
            foreach ($output as $name => $json) {
                $name = str_replace(DIRECTORY_SEPARATOR, '-', ltrim($name, DIRECTORY_SEPARATOR));
                $filename = $docDir . '/'.$name . '.json';
                file_put_contents($filename, $json);
            }
        }
    }
}
<?php namespace Darkaonline\L5Swagger;

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
            $excludeDirs = Config::get('l5-swagger.excludes');
            $swagger = \Swagger\scan($appDir, $excludeDirs);

            $filename = $docDir . '/api-docs.json';
            $swagger->saveAs($filename);
        }
    }
}
<?php

namespace L5Swagger;

use Illuminate\Support\ServiceProvider;
use L5Swagger\Console\GenerateDocsCommand;

class L5SwaggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $viewPath = __DIR__.'/../resources/views';
        $this->loadViewsFrom($viewPath, 'l5-swagger');

        // Publish a config file
        $configPath = __DIR__.'/../config/l5-swagger.php';
        $this->publishes([
            $configPath => config_path('l5-swagger.php'),
        ], 'config');

        //Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => config('l5-swagger.paths.views'),
        ], 'views');

        //Include routes
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        //Register commands
        $this->commands([GenerateDocsCommand::class]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__.'/../config/l5-swagger.php';
        $this->mergeConfigFrom($configPath, 'l5-swagger');

        $this->app->singleton('command.l5-swagger.generate', function ($app) {
            return new GenerateDocsCommand(
                $app->make(Generator::class)
            );
        });

        $this->app->bind(Generator::class, function ($app) {
            $annotationsDir = config('l5-swagger.paths.annotations');
            $docDir = config('l5-swagger.paths.docs');
            $docsFile = $docDir.'/'.config('l5-swagger.paths.docs_json', 'api-docs.json');
            $yamlDocsFile = $docDir.'/'.config('l5-swagger.paths.docs_yaml', 'api-docs.yaml');
            $excludedDirs = config('l5-swagger.paths.excludes');
            $constants = config('l5-swagger.constants') ?: [];
            $yamlCopyRequired = config('l5-swagger.generate_yaml_copy', false);
            $basePath = config('l5-swagger.paths.base');
            $swaggerVersion = config('l5-swagger.swagger_version');

            return new Generator(
                $annotationsDir,
                $docDir,
                $docsFile,
                $yamlDocsFile,
                $excludedDirs,
                $constants,
                $yamlCopyRequired,
                $basePath,
                $swaggerVersion
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.l5-swagger.generate',
        ];
    }
}

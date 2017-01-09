<?php

namespace L5Swagger;

use L5Swagger\Console\PublishCommand;
use Illuminate\Support\ServiceProvider;
use L5Swagger\Console\GenerateDocsCommand;
use L5Swagger\Console\PublishViewsCommand;
use L5Swagger\Console\PublishAssetsCommand;
use L5Swagger\Console\PublishConfigCommand;

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

        //Publish assets
        $this->publishes([
            __DIR__.'/../resources/assets' => config('l5-swagger.paths.assets'),
        ], 'assets');

        //Include routes

        \Route::group(['namespace' => 'L5Swagger'], function ($router) {
            require __DIR__.'/routes.php';
        });
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

        $this->app['command.l5-swagger.publish'] = $this->app->share(
            function () {
                return new PublishCommand();
            }
        );

        $this->app['command.l5-swagger.publish-config'] = $this->app->share(
            function () {
                return new PublishConfigCommand();
            }
        );

        $this->app['command.l5-swagger.publish-views'] = $this->app->share(
            function () {
                return new PublishViewsCommand();
            }
        );

        $this->app['command.l5-swagger.publish-assets'] = $this->app->share(
            function () {
                return new PublishAssetsCommand();
            }
        );

        $this->app['command.l5-swagger.generate'] = $this->app->share(
            function () {
                return new GenerateDocsCommand();
            }
        );

        $this->commands(
            'command.l5-swagger.publish',
            'command.l5-swagger.publish-config',
            'command.l5-swagger.publish-views',
            'command.l5-swagger.publish-assets',
            'command.l5-swagger.generate'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.l5-swagger.publish',
            'command.l5-swagger.publish-config',
            'command.l5-swagger.publish-views',
            'command.l5-swagger.publish-assets',
            'command.l5-swagger.generate',
        ];
    }
}

<?php namespace Darkaonline\L5Swagger;

use Illuminate\Support\ServiceProvider;
use Barryvdh\LaravelIdeHelper\Console\GeneratorCommand;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;

class L5SwaggerServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $viewPath = __DIR__.'/../resources/views';
        $this->loadViewsFrom($viewPath, 'ide-helper');

        $configPath = __DIR__ . '/../config/l5-swagger.php';
        $this->publishes([$configPath => config_path('l5-swagger.php')], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/l5-swagger.php';
        $this->mergeConfigFrom($configPath, 'l5-swagger');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        //
    }

}

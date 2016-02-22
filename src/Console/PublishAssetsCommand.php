<?php

namespace L5Swagger\Console;

use Illuminate\Console\Command;

class PublishAssetsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'l5-swagger:publish-assets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish assets to public';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->info('Publishing assets files');
        $this->call('vendor:publish', [
            '--provider' => 'L5Swagger\L5SwaggerServiceProvider',
            '--tag'      => ['assets'],
        ]);
    }
}

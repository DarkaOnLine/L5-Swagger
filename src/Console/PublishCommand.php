<?php

namespace L5Swagger\Console;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'l5-swagger:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish config, views, assets';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->info('Publishing all files');
        $this->call('vendor:publish', [
            '--provider' => 'L5Swagger\L5SwaggerServiceProvider',
        ]);
    }
}

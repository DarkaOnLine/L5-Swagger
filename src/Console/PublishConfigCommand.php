<?php

namespace L5Swagger\Console;

use Illuminate\Console\Command;

class PublishConfigCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'l5-swagger:publish-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish config';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->info('Publish config files');
        $this->call('vendor:publish', [
            '--provider' => 'L5Swagger\L5SwaggerServiceProvider',
            '--tag'      => ['config'],
        ]);
    }
}

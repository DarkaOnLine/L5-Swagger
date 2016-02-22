<?php

namespace L5Swagger\Console;

use Illuminate\Console\Command;

class PublishViewsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'l5-swagger:publish-views';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish views';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->info('Publishing view files');
        $this->call('vendor:publish', [
            '--provider' => 'L5Swagger\L5SwaggerServiceProvider',
            '--tag'      => ['views'],
        ]);
    }
}

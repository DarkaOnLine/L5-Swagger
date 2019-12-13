<?php

namespace L5Swagger\Console;

use Illuminate\Console\Command;
use L5Swagger\Generator;

class GenerateDocsCommand extends Command
{
    /**
     * @var L5Swagger\Generator
     */
    protected $generator;

    public function __construct(Generator $generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'l5-swagger:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate docs';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Regenerating docs');
        $this->generator->generateDocs();
    }
}

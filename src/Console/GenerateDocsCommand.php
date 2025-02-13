<?php

namespace L5Swagger\Console;

use Illuminate\Console\Command;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\GeneratorFactory;

class GenerateDocsCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'l5-swagger:generate {documentation?} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate docs';

    /**
     * @param  GeneratorFactory  $generatorFactory
     *
     * @throws L5SwaggerException
     */
    public function handle(GeneratorFactory $generatorFactory): void
    {
        $all = $this->option('all');

        if ($all) {
            /** @var array<string> $documentations */
            $documentations = array_keys(config('l5-swagger.documentations', []));

            foreach ($documentations as $documentation) {
                $this->generateDocumentation($generatorFactory, $documentation);
            }

            return;
        }

        $documentation = $this->argument('documentation');

        if (! $documentation) {
            $documentation = config('l5-swagger.default');
        }

        $this->generateDocumentation($generatorFactory, $documentation);
    }

    /**
     * Generates documentation using the specified generator factory.
     *
     * @param  GeneratorFactory  $generatorFactory  The factory used to create the documentation generator.
     * @param  string  $documentation  The name or identifier of the documentation to be generated.
     * @return void
     *
     * @throws L5SwaggerException
     */
    private function generateDocumentation(GeneratorFactory $generatorFactory, string $documentation): void
    {
        $this->info('Regenerating docs '.$documentation);

        $generator = $generatorFactory->make($documentation);
        $generator->generateDocs();
    }
}

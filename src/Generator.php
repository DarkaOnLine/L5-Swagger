<?php

namespace L5Swagger;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use L5Swagger\Exceptions\L5SwaggerException;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Server;
use OpenApi\Generator as OpenApiGenerator;
use OpenApi\OpenApiException;
use OpenApi\Pipeline;
use OpenApi\Util;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Dumper as YamlDumper;
use Symfony\Component\Yaml\Yaml;

class Generator
{
    public const OPEN_API_DEFAULT_SPEC_VERSION = '3.0.0';

    protected const SCAN_OPTION_PROCESSORS = 'processors';
    protected const SCAN_OPTION_PATTERN = 'pattern';
    protected const SCAN_OPTION_ANALYSER = 'analyser';
    protected const SCAN_OPTION_ANALYSIS = 'analysis';
    protected const SCAN_OPTION_EXCLUDE = 'exclude';

    protected const AVAILABLE_SCAN_OPTIONS = [
        self::SCAN_OPTION_PATTERN,
        self::SCAN_OPTION_ANALYSER,
        self::SCAN_OPTION_ANALYSIS,
        self::SCAN_OPTION_EXCLUDE,
    ];

    /**
     * @var string|array<string>
     */
    protected string|array $annotationsDir;

    protected string $docDir;

    protected string $docsFile;

    protected string $yamlDocsFile;

    /**
     * @var array<string>
     */
    protected array $excludedDirs;

    /**
     * @var array<string>
     */
    protected array $constants;

    protected ?OpenApi $openApi;

    protected bool $yamlCopyRequired;

    protected ?string $basePath;

    protected SecurityDefinitions $security;

    /**
     * @var array<string,mixed>
     */
    protected array $scanOptions;

    protected Filesystem $fileSystem;

    /**
     * Constructor to initialize documentation generation settings and dependencies.
     *
     * @param  array<string,mixed>  $paths  Array of paths including annotations, docs, excluded directories, and base path.
     * @param  array<string>  $constants  Array of constants to be used during documentation generation.
     * @param  bool  $yamlCopyRequired  Determines if a YAML copy of the documentation is required.
     * @param  SecurityDefinitions  $security  Security definitions for the documentation.
     * @param  array<string>  $scanOptions  Additional options for scanning files or directories.
     * @param  Filesystem|null  $filesystem  Filesystem instance, optional, defaults to a new Filesystem.
     * @return void
     */
    public function __construct(
        array $paths,
        array $constants,
        bool $yamlCopyRequired,
        SecurityDefinitions $security,
        array $scanOptions,
        ?Filesystem $filesystem = null
    ) {
        $this->annotationsDir = $paths['annotations'];
        $this->docDir = $paths['docs'];
        $this->docsFile = $this->docDir.DIRECTORY_SEPARATOR.($paths['docs_json'] ?? 'api-docs.json');
        $this->yamlDocsFile = $this->docDir.DIRECTORY_SEPARATOR.($paths['docs_yaml'] ?? 'api-docs.yaml');
        $this->excludedDirs = $paths['excludes'];
        $this->basePath = $paths['base'];
        $this->constants = $constants;
        $this->yamlCopyRequired = $yamlCopyRequired;
        $this->security = $security;
        $this->scanOptions = $scanOptions;

        $this->fileSystem = $filesystem ?? new Filesystem();
    }

    /**
     * Generate necessary documentation files by scanning and processing the required data.
     *
     * @return void
     *
     * @throws L5SwaggerException
     * @throws Exception
     */
    public function generateDocs(): void
    {
        $this->prepareDirectory()
            ->defineConstants()
            ->scanFilesForDocumentation()
            ->populateServers()
            ->saveJson()
            ->makeYamlCopy();
    }

    /**
     * Prepares the directory for storing documentation by ensuring it exists and is writable.
     *
     * @return self
     *
     * @throws L5SwaggerException If the directory is not writable or cannot be created.
     */
    protected function prepareDirectory(): self
    {
        if ($this->fileSystem->exists($this->docDir) && ! $this->fileSystem->isWritable($this->docDir)) {
            throw new L5SwaggerException('Documentation storage directory is not writable');
        }

        if (! $this->fileSystem->exists($this->docDir)) {
            $this->fileSystem->makeDirectory($this->docDir);
        }

        if (! $this->fileSystem->exists($this->docDir)) {
            throw new L5SwaggerException('Documentation storage directory could not be created');
        }

        return $this;
    }

    /**
     * Define and set constants if not already defined.
     *
     * @return self
     */
    protected function defineConstants(): self
    {
        if (! empty($this->constants)) {
            foreach ($this->constants as $key => $value) {
                defined($key) || define($key, $value);
            }
        }

        return $this;
    }

    /**
     * Scans files to generate documentation.
     *
     * @return self
     */
    protected function scanFilesForDocumentation(): self
    {
        $generator = $this->createOpenApiGenerator();
        $finder = $this->createScanFinder();

        // Analysis.
        $analysis = Arr::get($this->scanOptions, self::SCAN_OPTION_ANALYSIS);

        $this->openApi = $generator->generate($finder, $analysis);

        return $this;
    }

    /**
     * Create and configure an instance of OpenApiGenerator.
     *
     * @return OpenApiGenerator
     */
    protected function createOpenApiGenerator(): OpenApiGenerator
    {
        $generator = new OpenApiGenerator();

        if (! empty($this->scanOptions['default_processors_configuration'])
            && is_array($this->scanOptions['default_processors_configuration'])
        ) {
            $generator->setConfig($this->scanOptions['default_processors_configuration']);
        }

        $generator->setVersion(
            $this->scanOptions['open_api_spec_version'] ?? self::OPEN_API_DEFAULT_SPEC_VERSION
        );

        // Processors.
        $this->setProcessors($generator);

        // Analyser.
        $this->setAnalyser($generator);

        return $generator;
    }

    /**
     * Set the processors for the OpenAPI generator.
     *
     * @param  OpenApiGenerator  $generator  The OpenAPI generator instance to configure.
     * @return void
     */
    protected function setProcessors(OpenApiGenerator $generator): void
    {
        $processorClasses = Arr::get($this->scanOptions, self::SCAN_OPTION_PROCESSORS, []);
        $newPipeLine = [];

        $generator->getProcessorPipeline()->walk(
            function (callable $pipe) use ($processorClasses, &$newPipeLine) {
                $newPipeLine[] = $pipe;
                if ($pipe instanceof \OpenApi\Processors\BuildPaths) {
                    foreach ($processorClasses as $customProcessor) {
                        $newPipeLine[] = new $customProcessor();
                    }
                }
            }
        );

        if (! empty($newPipeLine)) {
            $generator->setProcessorPipeline(new Pipeline($newPipeLine));
        }
    }

    /**
     * Set the analyser for the OpenAPI generator based on scan options.
     *
     * @param  OpenApiGenerator  $generator  The OpenAPI generator instance.
     * @return void
     */
    protected function setAnalyser(OpenApiGenerator $generator): void
    {
        $analyser = Arr::get($this->scanOptions, self::SCAN_OPTION_ANALYSER);

        if (! empty($analyser)) {
            $generator->setAnalyser($analyser);
        }
    }

    /**
     * Create and return a Finder instance configured for scanning directories.
     *
     * @return Finder
     */
    protected function createScanFinder(): Finder
    {
        $pattern = Arr::get($this->scanOptions, self::SCAN_OPTION_PATTERN);
        $exclude = Arr::get($this->scanOptions, self::SCAN_OPTION_EXCLUDE);

        $exclude = ! empty($exclude) ? $exclude : $this->excludedDirs;

        return Util::finder($this->annotationsDir, $exclude, $pattern);
    }

    /**
     * Populate the servers list in the OpenAPI configuration using the base path.
     *
     * @return self
     */
    protected function populateServers(): self
    {
        if ($this->basePath !== null && $this->openApi !== null) {
            if (
                $this->openApi->servers === OpenApiGenerator::UNDEFINED // @phpstan-ignore-line
                || is_array($this->openApi->servers) === false // @phpstan-ignore-line
            ) {
                $this->openApi->servers = [];
            }

            $this->openApi->servers[] = new Server(['url' => $this->basePath]);
        }

        return $this;
    }

    /**
     * Saves the JSON data and applies security measures to the file.
     *
     * @return self
     *
     * @throws FileNotFoundException
     * @throws OpenApiException
     */
    protected function saveJson(): self
    {
        if ($this->openApi !== null) {
            $this->openApi->saveAs($this->docsFile);
        }

        $this->security->generate($this->docsFile);

        return $this;
    }

    /**
     * Creates a YAML copy of the OpenAPI documentation if required.
     *
     * This method converts the JSON documentation file to YAML format and saves it
     * to the specified file path when the YAML copy requirement is enabled.
     *
     * @return void
     *
     * @throws FileNotFoundException
     */
    protected function makeYamlCopy(): void
    {
        if ($this->yamlCopyRequired) {
            $yamlDocs = (new YamlDumper(2))->dump(
                json_decode($this->fileSystem->get($this->docsFile), true),
                20,
                0,
                Yaml::DUMP_OBJECT_AS_MAP ^ Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE
            );

            $this->fileSystem->put(
                $this->yamlDocsFile,
                $yamlDocs
            );
        }
    }
}

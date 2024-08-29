<?php

namespace L5Swagger;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use L5Swagger\Exceptions\L5SwaggerException;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Server;
use OpenApi\Generator as OpenApiGenerator;
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
     * @var string|array
     */
    protected $annotationsDir;

    /**
     * @var string
     */
    protected $docDir;

    /**
     * @var string
     */
    protected $docsFile;

    /**
     * @var string
     */
    protected $yamlDocsFile;

    /**
     * @var array
     */
    protected $excludedDirs;

    /**
     * @var array
     */
    protected $constants;

    /**
     * @var OpenApi
     */
    protected $openApi;

    /**
     * @var bool
     */
    protected $yamlCopyRequired;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var SecurityDefinitions
     */
    protected $security;

    /**
     * @var array
     */
    protected $scanOptions;

    /**
     * @var ?Filesystem
     */
    protected $fileSystem;

    /**
     * Generator constructor.
     *
     * @param  array  $paths
     * @param  array  $constants
     * @param  bool  $yamlCopyRequired
     * @param  SecurityDefinitions  $security
     * @param  array  $scanOptions
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
     * @throws L5SwaggerException
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
     * Check directory structure and permissions.
     *
     * @return Generator
     *
     * @throws L5SwaggerException
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
     * Define constant which will be replaced.
     *
     * @return Generator
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
     * Scan directory and create Swagger.
     *
     * @return Generator
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
     * Prepares generator for generating the documentation.
     *
     * @return OpenApiGenerator $generator
     */
    protected function createOpenApiGenerator(): OpenApiGenerator
    {
        $generator = new OpenApiGenerator();

        // Only from zircote/swagger-php 4
        if (! empty($this->scanOptions['default_processors_configuration'])
            && is_array($this->scanOptions['default_processors_configuration'])
            && method_exists($generator, 'setConfig')
        ) {
            $generator->setConfig($this->scanOptions['default_processors_configuration']);
        }

        // OpenApi spec version - only from zircote/swagger-php 4
        if (method_exists($generator, 'setVersion')) {
            $generator->setVersion(
                $this->scanOptions['open_api_spec_version'] ?? self::OPEN_API_DEFAULT_SPEC_VERSION
            );
        }

        // Processors.
        $this->setProcessors($generator);

        // Analyser.
        $this->setAnalyser($generator);

        return $generator;
    }

    /**
     * @param  OpenApiGenerator  $generator
     * @return void
     */
    protected function setProcessors(OpenApiGenerator $generator): void
    {
        $processorClasses = Arr::get($this->scanOptions, self::SCAN_OPTION_PROCESSORS, []);
        $processors = [];

        foreach ($generator->getProcessors() as $processor) {
            $processors[] = $processor;
            if ($processor instanceof \OpenApi\Processors\BuildPaths) {
                foreach ($processorClasses as $customProcessor) {
                    $processors[] = new $customProcessor();
                }
            }
        }

        if (! empty($processors)) {
            $generator->setProcessors($processors);
        }
    }

    /**
     * @param  OpenApiGenerator  $generator
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
     * Prepares finder for determining relevant files.
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
     * Generate servers section or basePath depending on Swagger version.
     *
     * @return Generator
     */
    protected function populateServers(): self
    {
        if ($this->basePath !== null) {
            if (! is_array($this->openApi->servers)) {
                $this->openApi->servers = [];
            }

            $this->openApi->servers[] = new Server(['url' => $this->basePath]);
        }

        return $this;
    }

    /**
     * Save documentation as json file.
     *
     * @return Generator
     *
     * @throws Exception
     */
    protected function saveJson(): self
    {
        $this->openApi->saveAs($this->docsFile);

        $this->security->generate($this->docsFile);

        return $this;
    }

    /**
     * Save documentation as yaml file.
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

<?php

namespace L5Swagger;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use L5Swagger\Exceptions\L5SwaggerException;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Server;
use OpenApi\Builder;
use OpenApi\Builder\Mode;
use OpenApi\Builder\Result;
use OpenApi\Generator as OpenApiGenerator;
use OpenApi\Loggers\DefaultLogger;
use OpenApi\OpenApiException;
use OpenApi\Spec\Server as SpecServer;
use OpenApi\Specification;
use OpenApi\Utils\Pipeline;
use OpenApi\Utils\SourceFinder;
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
    protected const SCAN_OPTION_MODE = 'mode';

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

    /**
     * The build result, unless generated with an `analysis`.
     */
    protected ?Result $result = null;

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
        $builder = $this->createOpenApiBuilder();

        // Analysis.
        $analysis = Arr::get($this->scanOptions, self::SCAN_OPTION_ANALYSIS);

        if ($builder === null && $analysis !== null) {
            if ($this->getMode() !== Mode::CLASSIC) {
                throw new L5SwaggerException('The `analysis` scan option requires the `classic` mode');
            }

            // The builder has no way to pass an analysis on to the generator.
            $this->openApi = $this->createOpenApiGenerator()->generate($this->createScanFinder(), $analysis);

            return $this;
        }

        $builder ??= $this->createDefaultBuilder();
        $result = $builder->addSource($this->createScanFinder())->build();

        // In classic mode the result holds the OpenApi annotation itself, not a copy: keeping a
        // reference lets populateServers() add the base path to what the result later saves.
        // Spec and hybrid mode have no annotation; the base path goes in through the Specification.
        $this->openApi = $result->openApi();
        $this->result = $result;

        return $this;
    }

    /**
     * Create and configure an instance of OpenApiGenerator.
     *
     * @return OpenApiGenerator
     */
    protected function createOpenApiGenerator(): OpenApiGenerator
    {
        $generator = $this->createGeneratorInstance();

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
     * Create the generator from `generator_factory`, or a new one.
     *
     * @return OpenApiGenerator
     */
    protected function createGeneratorInstance(): OpenApiGenerator
    {
        $factory = $this->scanOptions['generator_factory'] ?? null;

        if (is_string($factory) && is_subclass_of($factory, CustomGeneratorInterface::class)) {
            $factory = new $factory();
        }

        return $factory instanceof CustomGeneratorInterface
            ? $factory->create()
            : new OpenApiGenerator();
    }

    /**
     * Create an OpenApi Builder if a `builder_factory` is configured.
     *
     * The factory owns the pipeline: mode, augmenters, resolver and any other contributors.
     * The `processors`, `analyser`, `analysis` and `default_processors_configuration` scan
     * options configure the classic generator and are not applied here.
     *
     * @return Builder|null
     *
     * @throws L5SwaggerException
     */
    protected function createOpenApiBuilder(): ?Builder
    {
        $factory = $this->scanOptions['builder_factory'] ?? null;

        if ($factory === null) {
            return null;
        }

        if (($this->scanOptions['generator_factory'] ?? null) !== null) {
            throw new L5SwaggerException('Configure either `generator_factory` or `builder_factory`, not both');
        }

        if ($this->getMode() !== Mode::CLASSIC) {
            throw new L5SwaggerException('The `mode` scan option does not apply to `builder_factory`; set the mode on the builder');
        }

        if (is_string($factory) && is_subclass_of($factory, CustomBuilderInterface::class)) {
            $factory = new $factory();
        }

        if (! $factory instanceof CustomBuilderInterface) {
            throw new L5SwaggerException(
                '`builder_factory` must be an instance or class name of '.CustomBuilderInterface::class
            );
        }

        $builder = $factory->create()->setVersion(
            $this->scanOptions['open_api_spec_version'] ?? self::OPEN_API_DEFAULT_SPEC_VERSION
        );

        if ($this->basePath !== null) {
            if (! method_exists($builder, 'withSpecification')) { // @phpstan-ignore-line
                throw new L5SwaggerException('A base path with `builder_factory` requires zircote/swagger-php 6.12 or later');
            }

            $this->addBasePathServer($builder);
        }

        return $builder;
    }

    /**
     * Add the base path as a server through the Specification.
     *
     * Spec and hybrid mode only; classic mode gets the server in populateServers().
     *
     * @param  Builder  $builder
     * @return void
     */
    protected function addBasePathServer(Builder $builder): void
    {
        $basePath = $this->basePath;
        $builder->withSpecification(
            static fn (Specification $specification) => $specification->add(new SpecServer(url: $basePath))
        );
    }

    /**
     * The mode from the `mode` scan option, `classic` if not set.
     *
     * @return Mode
     *
     * @throws L5SwaggerException
     */
    protected function getMode(): Mode
    {
        $mode = $this->scanOptions[self::SCAN_OPTION_MODE] ?? null;

        if ($mode instanceof Mode) {
            return $mode;
        }

        return match ($mode ?: 'classic') {
            'classic' => Mode::CLASSIC,
            'hybrid' => Mode::HYBRID,
            'spec' => Mode::SPEC,
            default => throw new L5SwaggerException(
                'The `mode` scan option must be one of `classic`, `hybrid` or `spec`, got `'.$mode.'`'
            ),
        };
    }

    /**
     * Create an OpenApi Builder in the configured mode.
     *
     * In classic mode the builder generates through L5's own generator, so every classic scan
     * option applies as before. The generator the builder creates itself is not used: it logs
     * to the build result, which would hide the warnings Laravel turns into exceptions.
     *
     * Hybrid mode scans classic attributes and annotations through a generator without the
     * classic processors; see createHybridGenerator(). Spec mode reads spec attributes only and
     * uses no generator. In both, `default_processors_configuration` configures the augmenters,
     * apart from the generator's own `generator.*` keys in hybrid mode.
     *
     * @return Builder
     *
     * @throws L5SwaggerException
     */
    protected function createDefaultBuilder(): Builder
    {
        $mode = $this->getMode();

        if ($mode === Mode::CLASSIC) {
            return (new Builder())
                // Explicit: swagger-php 7 defaults to hybrid.
                ->setMode(Mode::CLASSIC)
                ->withGenerator(fn () => $this->createOpenApiGenerator());
        }

        $builder = (new Builder())
            ->setMode($mode)
            // The logger the generator uses in classic mode, so warnings still reach Laravel.
            ->setLogger(new DefaultLogger())
            ->setVersion($this->scanOptions['open_api_spec_version'] ?? self::OPEN_API_DEFAULT_SPEC_VERSION);

        [$generatorConfig, $augmenterConfig] = $this->splitProcessorsConfiguration($mode);

        if ($augmenterConfig !== []) {
            $builder->withAugmenters(static fn (Pipeline $augmenters) => $augmenters->configure($augmenterConfig));
        }

        if ($mode === Mode::HYBRID) {
            $builder->withGenerator(fn () => $this->createHybridGenerator($generatorConfig));
        }

        if ($this->basePath !== null) {
            $this->addBasePathServer($builder);
        }

        return $builder;
    }

    /**
     * Create the generator that scans classic attributes and annotations in hybrid mode.
     *
     * Hybrid mode runs no classic processors, so the pipeline holds the custom `processors`
     * only, in the order configured. An `after` position has nothing to refer to there and
     * is ignored.
     *
     * @param  array<string,mixed>  $config  The `generator.*` share of `default_processors_configuration`.
     * @return OpenApiGenerator
     */
    protected function createHybridGenerator(array $config): OpenApiGenerator
    {
        $generator = $this->createGeneratorInstance();

        if ($config !== []) {
            $generator->setConfig($config);
        }

        $generator->setVersion(
            $this->scanOptions['open_api_spec_version'] ?? self::OPEN_API_DEFAULT_SPEC_VERSION
        );

        $processors = [];
        foreach ($this->scanOptions[self::SCAN_OPTION_PROCESSORS] ?? [] as $entry) {
            $processor = is_array($entry) ? $entry['class'] : $entry;
            $processors[] = is_string($processor) ? new $processor() : $processor;
        }
        $generator->setProcessorPipeline(new Pipeline($processors));

        $this->setAnalyser($generator);

        return $generator;
    }

    /**
     * Split `default_processors_configuration` between the generator and the augmenters.
     *
     * Hybrid mode passes the `generator.*` keys to the generator and the rest to the
     * augmenters; spec mode passes everything to the augmenters. The same routing as the
     * `-c` option of swagger-php's command line.
     *
     * @param  Mode  $mode
     * @return array{0: array<string,mixed>, 1: array<int|string,mixed>} generator, augmenters
     */
    protected function splitProcessorsConfiguration(Mode $mode): array
    {
        $config = $this->scanOptions['default_processors_configuration'] ?? [];

        if (! is_array($config) || $config === []) {
            return [[], []];
        }

        if ($mode === Mode::SPEC) {
            return [[], $config];
        }

        $generator = [];
        $augmenters = [];
        foreach ($config as $key => $value) {
            if (is_string($key) && ($key === 'generator' || str_starts_with($key, 'generator.'))) {
                $generator[$key] = $value;

                continue;
            }

            $augmenters[$key] = $value;
        }

        return [$generator, $augmenters];
    }

    /**
     * Set the processors for the OpenAPI generator.
     *
     * @param  OpenApiGenerator  $generator  The OpenAPI generator instance to configure.
     * @return void
     */
    protected function setProcessors(OpenApiGenerator $generator): void
    {
        $processorConfigs = Arr::get($this->scanOptions, self::SCAN_OPTION_PROCESSORS, []);

        if (empty($processorConfigs)) {
            return;
        }

        $normalizedConfigs = [];
        foreach ($processorConfigs as $config) {
            $normalizedConfigs[] = is_array($config)
                ? $config
                : ['class' => $config, 'after' => \OpenApi\Processors\BuildPaths::class];
        }

        $newPipeLine = [];
        $generator->getProcessorPipeline()->walk(
            function (callable $pipe) use ($normalizedConfigs, &$newPipeLine) {
                $newPipeLine[] = $pipe;
                foreach ($normalizedConfigs as $entry) {
                    $after = $entry['after'];
                    if ($pipe instanceof $after) {
                        $processor = $entry['class'];
                        $newPipeLine[] = is_string($processor) ? new $processor() : $processor;
                    }
                }
            }
        );

        $generator->setProcessorPipeline(new Pipeline($newPipeLine));
    }

    /**
     * Set the analyzer for the OpenAPI generator based on scan options.
     *
     * @param  OpenApiGenerator  $generator  The OpenAPI generator instance.
     * @return void
     */
    protected function setAnalyser(OpenApiGenerator $generator): void
    {
        $analyser = Arr::get($this->scanOptions, self::SCAN_OPTION_ANALYSER);

        if (! empty($analyser)) {
            $generator->setAnalyser($analyser);

            return;
        }

        // Use AttributeAnnotationFactory for PHP 8.1+ native attributes
        $generator->setAnalyser(new \OpenApi\Analysers\ReflectionAnalyser([
            new \OpenApi\Analysers\AttributeAnnotationFactory(),
        ]));
    }

    /**
     * Create and return a Finder instance configured for scanning directories.
     *
     * @return Finder
     */
    protected function createScanFinder(): Finder
    {
        $pattern = Arr::get($this->scanOptions, self::SCAN_OPTION_PATTERN) ?: '*.php';
        $exclude = Arr::get($this->scanOptions, self::SCAN_OPTION_EXCLUDE);

        $exclude = ! empty($exclude) ? $exclude : $this->excludedDirs;

        $directory = is_array($this->annotationsDir) ? array_values($this->annotationsDir) : $this->annotationsDir;

        return new SourceFinder($directory, $exclude, $pattern);
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
        if ($this->result !== null) {
            // Any builder run, classic mode included: the result serialises the same annotation.
            $this->result->saveAs($this->docsFile, 'json');
        } elseif ($this->openApi !== null) {
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

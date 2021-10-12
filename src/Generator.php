<?php

namespace L5Swagger;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use L5Swagger\Exceptions\L5SwaggerException;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Server;
use function OpenApi\scan as openApiScan;
use Symfony\Component\Yaml\Dumper as YamlDumper;
use Symfony\Component\Yaml\Yaml;

class Generator
{
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
        array $scanOptions
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
        if (File::exists($this->docDir) && ! is_writable($this->docDir)) {
            throw new L5SwaggerException('Documentation storage directory is not writable');
        }

        if (! File::exists($this->docDir)) {
            File::makeDirectory($this->docDir);
        }

        if (! File::exists($this->docDir)) {
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
        $this->openApi = openApiScan(
            $this->annotationsDir,
            $this->getScanOptions()
        );

        return $this;
    }

    /**
     * Prepares options array for scanning files.
     *
     * @return array
     */
    protected function getScanOptions(): array
    {
        $options = [];

        $processorClasses = Arr::get($this->scanOptions, self::SCAN_OPTION_PROCESSORS, []);
        $processors = [];

        foreach (\OpenApi\Analysis::processors() as $processor) {
            $processors[] = $processor;
            if ($processor instanceof \OpenApi\Processors\BuildPaths) {
                foreach ($processorClasses as $customProcessor) {
                    $processors[] = new $customProcessor();
                }
            }
        }

        if (! empty($processors)) {
            $options[self::SCAN_OPTION_PROCESSORS] = $processors;
        }

        foreach (self::AVAILABLE_SCAN_OPTIONS as $optionKey) {
            $option = Arr::get($this->scanOptions, $optionKey);
            if (! empty($option)) {
                $options[$optionKey] = $option;
            }
        }

        // `scanOptions.exclude` option overwrites `paths.excludes` option but fallbacks to old config if not set
        $options[self::SCAN_OPTION_EXCLUDE] = ! empty($options[self::SCAN_OPTION_EXCLUDE]) ? $options[self::SCAN_OPTION_EXCLUDE] : $this->excludedDirs;

        return $options;
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
                json_decode(file_get_contents($this->docsFile), true),
                20,
                0,
                Yaml::DUMP_OBJECT_AS_MAP ^ Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE
            );

            file_put_contents(
                $this->yamlDocsFile,
                $yamlDocs
            );
        }
    }
}

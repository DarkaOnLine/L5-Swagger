<?php

namespace L5Swagger;

use Illuminate\Support\Facades\File;
use L5Swagger\Exceptions\L5SwaggerException;
use Symfony\Component\Yaml\Dumper as YamlDumper;
use Symfony\Component\Yaml\Yaml;

class Generator
{
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
     * @var \OpenApi\Annotations\OpenApi
     */
    protected $swagger;

    /**
     * @var bool
     */
    protected $yamlCopyRequired;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $swaggerVersion;

    public function __construct(
        $annotationsDir,
        string $docDir,
        string $docsFile,
        string $yamlDocsFile,
        array $excludedDirs,
        array $constants,
        bool $yamlCopyRequired,
        ?string $basePath,
        string $swaggerVersion
    ) {
        $this->annotationsDir = $annotationsDir;
        $this->docDir = $docDir;
        $this->docsFile = $docsFile;
        $this->yamlDocsFile = $yamlDocsFile;
        $this->excludedDirs = $excludedDirs;
        $this->constants = $constants;
        $this->yamlCopyRequired = $yamlCopyRequired;
        $this->basePath = $basePath;
        $this->swaggerVersion = $swaggerVersion;
    }

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
     * @throws L5SwaggerException
     *
     * @return Generator
     */
    protected function prepareDirectory(): self
    {
        if (File::exists($this->docDir) && ! is_writable($this->docDir)) {
            throw new L5SwaggerException('Documentation storage directory is not writable');
        }

        // delete all existing documentation
        if (File::exists($this->docDir)) {
            File::deleteDirectory($this->docDir);
        }

        if (File::exists($this->docDir)) {
            throw new L5SwaggerException('Documentation storage directory or files could not be deleted');
        }

        File::makeDirectory($this->docDir);

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
        if ($this->isOpenApi()) {
            $this->swagger = \OpenApi\scan(
                $this->annotationsDir,
                ['exclude' => $this->excludedDirs]
            );
        }

        if (! $this->isOpenApi()) {
            $this->swagger = \Swagger\scan(
                $this->annotationsDir,
                ['exclude' => $this->excludedDirs]
            );
        }

        return $this;
    }

    /**
     * Generate servers section or basePath depending on Swagger version.
     *
     * @return Generator
     */
    protected function populateServers(): self
    {
        if ($this->basePath !== null) {
            if ($this->isOpenApi()) {
                if (! is_array($this->swagger->servers)) {
                    $this->swagger->servers = [];
                }

                $this->swagger->servers[] = new \OpenApi\Annotations\Server(['url' => $this->basePath]);
            }

            if (! $this->isOpenApi()) {
                $this->swagger->basePath = $this->basePath;
            }
        }

        return $this;
    }

    /**
     * Save documentation as json file.
     *
     * @throws \Exception
     *
     * @return Generator
     */
    protected function saveJson(): self
    {
        $this->swagger->saveAs($this->docsFile);

        $security = new SecurityDefinitions();
        $security->generate($this->docsFile);

        return $this;
    }

    /**
     * Save documentation as yaml file.
     */
    protected function makeYamlCopy(): void
    {
        if ($this->yamlCopyRequired) {
            file_put_contents(
                $this->yamlDocsFile,
                (new YamlDumper(2))->dump(
                    json_decode(file_get_contents($this->docsFile), true),
                    20,
                    0,
                    Yaml::DUMP_OBJECT_AS_MAP ^ Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE
                )
            );
        }
    }

    /**
     * Check which documentation version is used.
     *
     * @return bool
     */
    protected function isOpenApi(): bool
    {
        return version_compare($this->swaggerVersion, '3.0', '>=');
    }
}

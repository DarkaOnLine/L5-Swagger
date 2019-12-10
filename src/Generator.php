<?php

namespace L5Swagger;

use Illuminate\Support\Facades\File;
use L5Swagger\Exceptions\L5SwaggerException;
use Symfony\Component\Yaml\Dumper as YamlDumper;

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
        $docDir,
        $docsFile,
        $yamlDocsFile,
        $excludedDirs,
        $constants,
        $yamlCopyRequired,
        $basePath,
        $swaggerVersion
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

    public function generateDocs()
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
     */
    protected function prepareDirectory()
    {
        if (File::exists($this->docDir) && ! is_writable($this->docDir)) {
            throw new L5SwaggerException('Documentation storage directory is not writable');
        }

        // delete all existing documentation
        if (File::exists($this->docDir)) {
            File::deleteDirectory($this->docDir);
        }

        File::makeDirectory($this->docDir);

        return $this;
    }

    /**
     * Define constant which will be replaced.
     *
     * @return Generator
     */
    protected function defineConstants()
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
    protected function scanFilesForDocumentation()
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
    protected function populateServers()
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
     * @return Generator
     */
    protected function saveJson()
    {
        $this->swagger->saveAs($this->docsFile);

        $security = new SecurityDefinitions();
        $security->generate($this->docsFile);

        return $this;
    }

    /**
     * Save documentation as yaml file.
     *
     * @return Generator
     */
    protected function makeYamlCopy()
    {
        if ($this->yamlCopyRequired) {
            file_put_contents(
                $this->yamlDocsFile,
                (new YamlDumper(2))->dump(json_decode(file_get_contents($this->docsFile), true), 20)
            );
        }
    }

    /**
     * Check which documentation version is used.
     *
     * @return bool
     */
    protected function isOpenApi()
    {
        return version_compare($this->swaggerVersion, '3.0', '>=');
    }
}

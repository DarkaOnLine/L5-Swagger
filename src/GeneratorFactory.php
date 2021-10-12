<?php

namespace L5Swagger;

use L5Swagger\Exceptions\L5SwaggerException;

class GeneratorFactory
{
    /**
     * @var ConfigFactory
     */
    private $configFactory;

    public function __construct(ConfigFactory $configFactory)
    {
        $this->configFactory = $configFactory;
    }

    /**
     * Make Generator Instance.
     *
     * @param  string  $documentation
     * @return Generator
     *
     * @throws L5SwaggerException
     */
    public function make(string $documentation): Generator
    {
        $config = $this->configFactory->documentationConfig($documentation);

        $paths = $config['paths'];
        $scanOptions = $config['scanOptions'] ?? [];
        $constants = $config['constants'] ?? [];
        $yamlCopyRequired = $config['generate_yaml_copy'] ?? false;

        $secSchemesConfig = $config['securityDefinitions']['securitySchemes'] ?? [];
        $secConfig = $config['securityDefinitions']['security'] ?? [];

        $security = new SecurityDefinitions($secSchemesConfig, $secConfig);

        return new Generator(
            $paths,
            $constants,
            $yamlCopyRequired,
            $security,
            $scanOptions
        );
    }
}

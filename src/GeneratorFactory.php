<?php

namespace L5Swagger;

use L5Swagger\Exceptions\L5SwaggerException;

class GeneratorFactory
{
    public function __construct(private readonly ConfigFactory $configFactory)
    {
    }

    /**
     * Creates and returns a new Generator instance based on the provided documentation configuration.
     *
     * @param  string  $documentation  The name or identifier of the documentation to generate.
     * @return Generator The configured Generator instance.
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

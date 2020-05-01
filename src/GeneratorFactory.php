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
     * @throws L5SwaggerException
     *
     * @return Generator
     */
    public function make(string $documentation): Generator
    {
        $config = $this->configFactory->documentationConfig($documentation);

        $paths = $config['paths'];
        $constants = $config['constants'] ?: [];
        $yamlCopyRequired = $config['generate_yaml_copy'] ?? false;

        $security = new SecurityDefinitions($config['security']);

        return new Generator(
            $paths,
            $constants,
            $yamlCopyRequired,
            $security
        );
    }
}

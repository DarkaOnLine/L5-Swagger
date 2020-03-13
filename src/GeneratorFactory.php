<?php

namespace L5Swagger;

use L5Swagger\Exceptions\L5SwaggerException;

class GeneratorFactory
{
    /**
     * Make Generator Instance.
     *
     * @throws L5SwaggerException
     *
     * @return Generator
     */
    public function make(string $documentation): Generator
    {
        $config = $this->documentationConfig($documentation);

        $paths = $config['paths'];
        $constants = $config['constants'] ?: [];
        $yamlCopyRequired = $config['generate_yaml_copy'] ?? false;
        $swaggerVersion = $config['swagger_version'];

        $security = new SecurityDefinitions($swaggerVersion, $config['security']);

        return new Generator(
            $paths,
            $constants,
            $yamlCopyRequired,
            $swaggerVersion,
            $security
        );
    }

    /**
     * Get documentation config.
     *
     * @throws L5SwaggerException
     *
     * @return array
     */
    protected function documentationConfig(string $documentation): array
    {
        $documentations = config('l5-swagger.documentations');

        if (! isset($documentations[$documentation])) {
            throw new L5SwaggerException('Documentation config not found');
        }

        return $documentations[$documentation];
    }
}

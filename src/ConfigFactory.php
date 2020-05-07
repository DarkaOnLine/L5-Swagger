<?php

namespace L5Swagger;

use L5Swagger\Exceptions\L5SwaggerException;

class ConfigFactory
{
    /**
     * Get documentation config.
     *
     * @param string|null $documentation
     * @throws L5SwaggerException
     * @return array
     */
    public function documentationConfig(?string $documentation = null): array
    {
        if ($documentation === null) {
            $documentation = config('l5-swagger.default');
        }

        $defaults = config('l5-swagger.defaults', []);
        $documentations = config('l5-swagger.documentations', []);

        if (! isset($documentations[$documentation])) {
            throw new L5SwaggerException('Documentation config not found');
        }

        return $this->mergeConfig($defaults, $documentations[$documentation]);
    }

    private function mergeConfig(array $defaults, array $config): array
    {
        $merged = $defaults;

        foreach ($config as $key => &$value) {
            if (isset($defaults[$key])
                && $this->isAssociativeArray($defaults[$key])
                && $this->isAssociativeArray($value)
            ) {
                $merged[$key] = $this->mergeConfig($defaults[$key], $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    private function isAssociativeArray($value): bool
    {
        return is_array($value) && count(array_filter(array_keys($value), 'is_string')) > 0;
    }
}

<?php

namespace L5Swagger;

use L5Swagger\Exceptions\L5SwaggerException;

class ConfigFactory
{
    /**
     * Retrieves and merges the configuration for the specified documentation.
     *
     * @param  string|null  $documentation  The name of the documentation configuration to retrieve.
     *                                      If null, the default documentation configuration is used.
     * @return array<string, mixed> The merged configuration for the specified documentation.
     *
     * @throws L5SwaggerException If the specified documentation configuration is not found.
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

    /**
     * Merges two configuration arrays recursively, with the values from the second array
     * overriding those in the first array when keys overlap.
     *
     * @param  array<string, mixed>  $defaults  The default configuration array.
     * @param  array<string, mixed>  $config  The configuration array to merge into the defaults.
     * @return array<string, mixed> The merged configuration array.
     */
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

    /**
     * Determines whether a given value is an associative array.
     *
     * @param  mixed  $value  The value to be checked.
     * @return bool True if the value is an associative array, false otherwise.
     */
    private function isAssociativeArray(mixed $value): bool
    {
        return is_array($value) && count(array_filter(array_keys($value), 'is_string')) > 0;
    }
}

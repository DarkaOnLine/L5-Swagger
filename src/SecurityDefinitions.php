<?php

namespace L5Swagger;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class SecurityDefinitions
{
    /**
     * @param  array<string,mixed>  $schemasConfig
     * @param  array<string,mixed>  $securityConfig
     */
    public function __construct(
        private readonly array $schemasConfig = [],
        private readonly array $securityConfig = []
    ) {
    }

    /**
     * Reads in the l5-swagger configuration and appends security settings to documentation.
     *
     * @param  string  $filename  The path to the generated json documentation
     *
     * @throws FileNotFoundException
     */
    public function generate(string $filename): void
    {
        $fileSystem = new Filesystem();

        $documentation = collect(
            json_decode($fileSystem->get($filename))
        );

        if (! empty($this->schemasConfig)) {
            $documentation = $this->injectSecuritySchemes($documentation, $this->schemasConfig);
        }

        if (! empty($this->securityConfig)) {
            $documentation = $this->injectSecurity($documentation, $this->securityConfig);
        }

        $fileSystem->put(
            $filename,
            $documentation->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Inject security schemes settings.
     *
     * @param  Collection<int|string, mixed>  $documentation  The parse json
     * @param  array<string,mixed>  $config  The securityScheme settings from l5-swagger
     * @return Collection<int|string, mixed>
     */
    protected function injectSecuritySchemes(Collection $documentation, array $config): Collection
    {
        $components = collect();
        if ($documentation->has('components')) {
            $components = collect($documentation->get('components'));
        }

        $securitySchemes = collect();
        if ($components->has('securitySchemes')) {
            $securitySchemes = collect($components->get('securitySchemes'));
        }

        foreach ($config as $key => $cfg) {
            $securitySchemes->offsetSet($key, $this->arrayToObject($cfg));
        }

        $components->offsetSet('securitySchemes', $securitySchemes);

        $documentation->offsetSet('components', $components);

        return $documentation;
    }

    /**
     * Inject security settings.
     *
     * @param  Collection<int|string, mixed>  $documentation  The parse json
     * @param  array<string, mixed>  $config  The security settings from l5-swagger
     * @return Collection<int|string, mixed>
     */
    protected function injectSecurity(Collection $documentation, array $config): Collection
    {
        $security = collect();
        if ($documentation->has('security')) {
            $security = collect($documentation->get('security'));
        }

        foreach ($config as $key => $cfg) {
            if (! empty($cfg)) {
                $security->put($key, $this->arrayToObject($cfg));
            }
        }

        if ($security->count()) {
            $documentation->offsetSet('security', $security);
        }

        return $documentation;
    }

    /**
     * Converts an array to an object.
     *
     * @param  $array<string,  mixed>
     * @return object
     */
    protected function arrayToObject(mixed $array): mixed
    {
        return json_decode(json_encode($array) ?: '');
    }
}

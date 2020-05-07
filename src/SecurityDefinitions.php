<?php

namespace L5Swagger;

use Illuminate\Support\Collection;

class SecurityDefinitions
{
    /**
     * @var array
     */
    protected $securitySchemesConfig;

    /**
     * @var array
     */
    protected $securityConfig;

    /**
     * SecurityDefinitions constructor.
     * @param array $securitySchemesConfig
     * @param array $securityConfig
     */
    public function __construct(array $securitySchemesConfig = [], array $securityConfig = [])
    {
        $this->securitySchemesConfig = $securitySchemesConfig;
        $this->securityConfig = $securityConfig;
    }

    /**
     * Reads in the l5-swagger configuration and appends security settings to documentation.
     *
     * @param string $filename The path to the generated json documentation
     */
    public function generate($filename)
    {
        $documentation = collect(
            json_decode(file_get_contents($filename))
        );

        if (is_array($this->securitySchemesConfig) && ! empty($this->securitySchemesConfig)) {
            $documentation = $this->injectSecuritySchemes($documentation, $this->securitySchemesConfig);
        }

        if (is_array($this->securityConfig) && ! empty($this->securityConfig)) {
            $documentation = $this->injectSecurity($documentation, $this->securityConfig);
        }

        file_put_contents(
            $filename,
            $documentation->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Inject security schemes settings.
     *
     * @param Collection $documentation The parse json
     * @param array $config The securityScheme settings from l5-swagger
     *
     * @return Collection
     */
    protected function injectSecuritySchemes(Collection $documentation, array $config)
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
            $securitySchemes->offsetSet($key, self::arrayToObject($cfg));
        }

        $components->offsetSet('securitySchemes', $securitySchemes);

        $documentation->offsetSet('components', $components);

        return $documentation;
    }

    /**
     * Inject security settings.
     *
     * @param Collection $documentation The parse json
     * @param array $config The security settings from l5-swagger
     *
     * @return Collection
     */
    protected function injectSecurity(Collection $documentation, array $config)
    {
        $security = collect();
        if ($documentation->has('security')) {
            $security = collect($documentation->get('security'));
        }

        foreach ($config as $key => $cfg) {
            $security->offsetSet($key, self::arrayToObject($cfg));
        }

        $documentation->offsetSet('security', $security);

        return $documentation;
    }

    /**
     * Converts an array to an object.
     *
     * @param $array
     *
     * @return object
     */
    public static function arrayToObject($array)
    {
        return json_decode(json_encode($array));
    }
}

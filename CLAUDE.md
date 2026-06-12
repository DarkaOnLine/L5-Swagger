# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

L5-Swagger is a Laravel package that integrates swagger-php and swagger-ui into Laravel. It does NOT implement the OpenAPI spec — it wraps [swagger-php](https://github.com/zircote/swagger-php) for annotation scanning and [swagger-ui](https://github.com/swagger-api/swagger-ui) for the documentation frontend.

Supports multiple independent documentation sets, each with its own routes, paths, and settings via a two-level config: `defaults` (shared) merged with `documentations.{name}` (per-doc overrides). `ConfigFactory::mergeConfig()` recursively merges associative arrays.

## Development Commands

```bash
# Run all tests
composer run-script phpunit

# Run a single test file
vendor/bin/phpunit tests/Unit/GeneratorTest.php

# Run a single test method
vendor/bin/phpunit --filter testCanGenerateApiJsonFile

# Static analysis (PHPStan level 8)
composer run-script analyse
```

## Architecture

### Generation Pipeline

`GeneratorFactory::make(documentation)` → creates `Generator` with merged config → `Generator::generateDocs()` chains:

1. `prepareDirectory()` — ensure output dir exists/writable
2. `defineConstants()` — define PHP constants from config (usable in annotations)
3. `scanFilesForDocumentation()` — swagger-php scans PHP files using `ReflectionAnalyser` + `AttributeAnnotationFactory`
4. `populateServers()` — injects base path as a Server URL
5. `saveJson()` — writes JSON, then `SecurityDefinitions::generate()` injects security schemes
6. `makeYamlCopy()` — optional YAML conversion via `symfony/yaml`

Custom processors can be positioned relative to any existing processor via `scanOptions.processors` using `['class' => ..., 'after' => ...]` syntax. A `CustomGeneratorInterface` implementation can be provided via `scanOptions.generator_factory` to supply a pre-configured `OpenApi\Generator`.

### Service Container Bindings

`L5SwaggerServiceProvider` binds `Generator::class` as a factory — each resolution creates a new Generator via `GeneratorFactory::make()` using the `l5-swagger.default` documentation name. The `GenerateDocsCommand` is registered as a singleton under `command.l5-swagger.generate`.

### Routing

Routes are registered dynamically in `src/routes.php` by iterating all documentation sets. Each set gets up to 4 routes (api UI, docs JSON/YAML, assets, OAuth2 callback), all wrapped with the `Config` middleware that sets the active documentation context. Route names follow `l5-swagger.{documentation}.{type}`.

### Helpers

`swagger_ui_dist_path()` resolves swagger-ui asset paths with an allowlist of permitted files. `l5_swagger_asset()` generates versioned (cache-busted) URLs for those assets.

## Testing

Tests use Orchestra Testbench (`Tests\Unit\TestCase` extends `OrchestraTestCase`) for a full Laravel environment.

Key patterns:
- `$this->fileSystem` is a PHPUnit mock of `Filesystem`, injected into `Generator` via reflection in `makeGeneratorWithMockedFileSystem()`
- `setAnnotationsPath()` reconfigures the app to use `tests/storage/annotations/OpenApi/` fixtures and rebuilds the generator
- `setCustomDocsFileName()` overrides the output filename for JSON/YAML format tests
- `copyAssets()` copies swagger-ui dist into testbench's vendor directory in setUp; `tearDown` cleans up generated docs
- PHPUnit attributes: `#[TestDox('...')]`, `#[CoversClass(...)]`

Test fixtures live in `tests/storage/annotations/OpenApi/` with sample PHP attribute annotations.

## Code Style

- **StyleCI**: `preset: laravel` — enforced automatically
- **PHPStan**: Level 8, analyzes `src/` and `tests/Unit/`, ignores `argument.templateType`
- PHP 8.2+ required; uses constructor property promotion, union types, named arguments
- Supports Laravel 11.44+, 12.1+, and 13.0+

## Environment Variables

| Variable | Purpose |
|---|---|
| `L5_SWAGGER_GENERATE_ALWAYS` | Regenerate docs on every request (dev mode) |
| `L5_SWAGGER_GENERATE_YAML_COPY` | Also create YAML version |
| `L5_SWAGGER_CONST_HOST` | Default host constant for annotations |
| `L5_SWAGGER_OPEN_API_SPEC_VERSION` | `3.0.0` (default) or `3.1.0` |
| `L5_SWAGGER_UI_DARK_MODE` | Dark mode for Swagger UI |
| `SWAGGER_VERSION` | Used in tests to set OpenAPI version |

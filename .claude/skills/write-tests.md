---
name: write-tests
description: Write PHPUnit tests for the L5-Swagger Laravel package using Orchestra Testbench, following project conventions for mocking, config manipulation, and PHPUnit attributes
allowed-tools: Read, Edit, Write, Bash(vendor/bin/phpunit *), Bash(composer run-script phpunit), Bash(composer run-script analyse), Bash(grep *), Bash(find *), Bash(ls *), Bash(diff *), Bash(cat *)
---

# Write Tests for L5-Swagger

Generate PHPUnit tests for the L5-Swagger package following its established patterns.

## Context

- Test runner: !`composer run-script phpunit -- --version 2>/dev/null | head -1`
- Existing test files: !`ls tests/Unit/*.php 2>/dev/null | xargs -I{} basename {}`
- Source files: !`ls src/*.php src/**/*.php 2>/dev/null | grep -v vendor`

## Project Test Architecture

All tests extend `Tests\Unit\TestCase` which extends Orchestra Testbench's `OrchestraTestCase`. This gives every test a full Laravel application with the `L5SwaggerServiceProvider` registered.

### Base TestCase provides:

| Property / Method | Purpose |
|---|---|
| `$this->configFactory` | `ConfigFactory` instance resolved from the app container |
| `$this->generator` | `Generator` instance resolved from the app container |
| `$this->fileSystem` | PHPUnit mock of `Illuminate\Filesystem\Filesystem` (created via `createMock`) |
| `setAnnotationsPath()` | Points config to `tests/storage/annotations/OpenApi/` fixtures, enables `generate_always` and `generate_yaml_copy`, sets `L5_SWAGGER_CONST_HOST` constant, rebuilds the generator |
| `makeGeneratorWithMockedFileSystem()` | Injects `$this->fileSystem` mock into the generator via reflection |
| `setCustomDocsFileName($name, $type)` | Overrides docs filename in config for JSON or YAML |
| `crateJsonDocumentationFile()` | Creates a minimal `{}` JSON docs file |
| `createYamlDocumentationFile()` | Creates an empty YAML docs file |
| `jsonDocsFile()` | Returns absolute path to the JSON docs file (creates dir if needed) |
| `yamlDocsFile()` | Returns absolute path to the YAML docs file (creates dir if needed) |
| `copyAssets()` | Copies swagger-ui dist into testbench vendor dir (runs in setUp) |
| `deleteAssets()` | Removes the copied swagger-ui assets |

### tearDown cleanup

The base TestCase automatically deletes generated JSON/YAML docs files and the docs directory in `tearDown()`. You do NOT need to clean up generated files.

## Instructions

### Step 1: Determine what to test

Read the source file(s) the user wants tested. Identify:
- Public methods and their behavior
- Error/exception paths
- Config-driven behavior branches
- Interactions with the filesystem or external dependencies

### Step 2: Create or edit the test file

**File location**: `tests/Unit/{ClassName}Test.php`

**Required class-level attributes** (PHPUnit 11 attributes, not annotations):
```php
#[TestDox('Human readable class description')]
#[CoversClass(FullyQualifiedClassName::class)]
```

**Required test method conventions**:
- Method names: `testItDoesX` or `testCanDoX` (camelCase, descriptive)
- Visibility: `public function testXxx(): void`
- Add `@throws` docblock for expected exceptions
- Use `expectException()` and `expectExceptionMessage()` for exception tests

### Step 3: Follow these patterns based on what you're testing

#### Pattern A: Testing Generator behavior with mocked filesystem

Use when testing `Generator` methods that interact with the filesystem (directory creation, file writing, permission checks).

```php
public function testItThrowsExceptionIfSomethingFails(): void
{
    $this->setAnnotationsPath();

    $config = $this->configFactory->documentationConfig();
    $docs = $config['paths']['docs'];

    // Set up filesystem mock expectations
    $this->fileSystem
        ->expects($this->once())
        ->method('exists')
        ->with($docs)
        ->willReturn(true);

    // ... more mock setup ...

    $this->expectException(L5SwaggerException::class);
    $this->expectExceptionMessage('Expected error message');

    // IMPORTANT: call makeGeneratorWithMockedFileSystem() AFTER mock setup
    $this->makeGeneratorWithMockedFileSystem();
    $this->generator->generateDocs();
}
```

#### Pattern B: Testing full generation pipeline (integration)

Use when testing that docs generate correctly with specific config.

```php
public function testCanGenerateWithSpecificConfig(): void
{
    $this->setAnnotationsPath();

    // Optionally override config
    $cfg = config('l5-swagger.documentations.default');
    $cfg['paths']['base'] = 'https://custom-server.url';
    config(['l5-swagger' => [
        'default' => 'default',
        'documentations' => ['default' => $cfg],
        'defaults' => config('l5-swagger.defaults'),
    ]]);

    $this->generator->generateDocs();

    $this->assertFileExists($this->jsonDocsFile());

    // Verify via HTTP response
    $this->get(route('l5-swagger.default.docs'))
        ->assertSee('expected content')
        ->assertStatus(200);
}
```

#### Pattern C: Testing routes and HTTP responses

Use when testing controller behavior, middleware, or route registration.

```php
public function testRouteReturnsExpectedResponse(): void
{
    // For tests needing generated docs, call setAnnotationsPath() first
    // For tests checking behavior without docs, don't call it

    $this->get(route('l5-swagger.default.docs'))
        ->assertStatus(200)
        ->assertSee('expected')
        ->assertHeader('Content-Type', 'application/json');
}
```

#### Pattern D: Testing config merging

Use when testing `ConfigFactory` behavior.

```php
#[DataProvider('configDataProvider')]
public function testConfigMergesCorrectly(array $data, array $expected): void
{
    config(['l5-swagger' => array_merge($data, [
        'defaults' => [/* base defaults */],
    ])]);

    $config = $this->configFactory->documentationConfig();

    $this->assertSame($expected['key'], $config['key']);
}

public static function configDataProvider(): \Generator
{
    yield 'descriptive case name' => [
        'data' => [/* input */],
        'expected' => [/* expected output */],
    ];
}
```

#### Pattern E: Mocking the Generator via GeneratorFactory

Use when testing controllers/routes that call `generateDocs()` and you want to control generator behavior without actual generation.

```php
public function testBehaviorWhenGenerationFails(): void
{
    $mockGenerator = $this->createMock(Generator::class);
    $mockGeneratorFactory = $this->createMock(GeneratorFactory::class);
    $mockGeneratorFactory->method('make')->willReturn($mockGenerator);
    app()->extend(GeneratorFactory::class, function () use ($mockGeneratorFactory) {
        return $mockGeneratorFactory;
    });

    $mockGenerator->expects($this->once())
        ->method('generateDocs')
        ->willThrowException(new L5SwaggerException());

    $this->get(route('l5-swagger.default.docs'))->assertNotFound();
}
```

### Step 4: Config manipulation pattern

When overriding config, always preserve the full structure:

```php
config(['l5-swagger' => [
    'default' => 'default',
    'documentations' => [
        'default' => $cfg,  // your modified config
    ],
    'defaults' => config('l5-swagger.defaults'),
]]);
```

After changing config that affects the generator, call `$this->makeGenerator()` to rebuild it.

### Step 5: Run and verify with coverage gate

New tests must never decrease code coverage. Follow this sequence:

#### 5a. Capture baseline coverage BEFORE writing tests

```bash
vendor/bin/phpunit --coverage-text --only-summary-for-coverage-text 2>&1 | tee /tmp/l5-coverage-before.txt
```

Extract the baseline percentages:

```bash
grep -E 'Lines:|Methods:|Classes:' /tmp/l5-coverage-before.txt
```

Record both **Lines** and **Methods** percentages — these are the numbers that must not drop.

#### 5b. Run the new/modified tests in isolation

```bash
vendor/bin/phpunit tests/Unit/YourNewTest.php --testdox
```

#### 5c. Run the full suite with coverage AFTER adding tests

```bash
vendor/bin/phpunit --coverage-text --only-summary-for-coverage-text 2>&1 | tee /tmp/l5-coverage-after.txt
```

#### 5d. Compare coverage

```bash
diff /tmp/l5-coverage-before.txt /tmp/l5-coverage-after.txt
```

Verify:
- **Lines coverage**: must be >= baseline
- **Methods coverage**: must be >= baseline

If coverage decreased, identify the cause:
1. A new test file with `#[CoversClass]` pulled in a class that was previously uncovered — add tests for the uncovered methods
2. A test is covering code paths that were already covered but skipping others — add assertions for the missing branches
3. A new fixture or helper class landed under `src/` — it needs its own tests

**Do not proceed until coverage is equal to or higher than the baseline.**

#### 5e. Run static analysis

```bash
composer run-script analyse
```

## Important Rules

- **Coverage must not decrease.** Always capture baseline coverage before writing tests and verify it afterwards. If a `#[CoversClass]` attribute pulls in a class with uncovered methods, you must add tests for those methods too — not just remove the attribute. This is a hard gate: do not report the task as complete until coverage is verified equal or higher.
- Use PHP 8.2+ features: constructor property promotion, union types, named arguments, match expressions
- Use PHPUnit 11 **attributes** (`#[TestDox]`, `#[CoversClass]`, `#[DataProvider]`), NOT docblock annotations (`@testdox`, `@covers`, `@dataProvider`)
- Data providers must be `public static` methods returning `\Generator` (using `yield`)
- Fixture annotations live in `tests/storage/annotations/OpenApi/` — read existing ones before creating new fixtures
- The `$this->fileSystem` mock is created fresh via `#[Before]` attribute before each test — no shared mock state between tests
- Route names follow the pattern `l5-swagger.{documentation}.{type}` where type is `api`, `docs`, `asset`, or `oauth2_callback`
- The test environment sets `SWAGGER_VERSION=3.0` and `APP_KEY` via `phpunit.xml`
- StyleCI enforces Laravel preset — don't fight the code style

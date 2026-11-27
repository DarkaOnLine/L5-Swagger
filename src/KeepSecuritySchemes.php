<?php

namespace L5Swagger;

use OpenApi\Augmenter\Cleanup;
use OpenApi\Augmenter\Group;
use OpenApi\Specification;
use OpenApi\Specification\ComponentName;
use OpenApi\Utils\PipeInterface;

/**
 * Keeps the security schemes the l5-swagger `security` setting names.
 *
 * SecurityDefinitions adds that setting to the saved documentation, after the build, so swagger-php's
 * `Cleanup` sees nothing referencing those schemes and removes them. This marks them to be kept
 * instead. Runs in hybrid and spec mode, before `Cleanup`.
 *
 * @implements PipeInterface<Specification>
 */
class KeepSecuritySchemes implements PipeInterface
{
    /**
     * @param  list<string>  $schemes  The scheme names to keep.
     */
    public function __construct(private readonly array $schemes)
    {
    }

    /**
     * @param  Specification  $payload
     */
    public function __invoke(mixed $payload): mixed
    {
        foreach ($payload->securitySchemes as $scheme) {
            if (in_array(ComponentName::of($scheme), $this->schemes, true)) {
                $scheme->setMeta(Cleanup::class, false);
            }
        }

        return null;
    }

    public function group(): string|\BackedEnum
    {
        return Group::Reduce;
    }
}

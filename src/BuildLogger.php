<?php

namespace L5Swagger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Reports what swagger-php logs while building in hybrid or spec mode.
 *
 * Errors are raised as PHP warnings, which Laravel turns into exceptions. An error means something
 * was left out of the document. Warnings, notices and info go to Laravel's log instead, so they do
 * not stop the build. The document is still valid, with a keyword adapted or omitted, such as `const`
 * in OpenAPI 3.0, or an unreferenced component removed. Debug messages are ignored.
 */
class BuildLogger extends AbstractLogger
{
    /**
     * @param  mixed  $level
     * @param  string|\Stringable  $message
     * @param  array<mixed>  $context
     */
    public function log($level, $message, array $context = []): void
    {
        if ($level === LogLevel::DEBUG) {
            return;
        }

        if (in_array($level, [LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO], true)) {
            logger()->log($level, (string) $message, $context);

            return;
        }

        trigger_error((string) $message, E_USER_WARNING);
    }
}

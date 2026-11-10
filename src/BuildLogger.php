<?php

namespace L5Swagger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Reports what swagger-php logs while building in hybrid or spec mode.
 *
 * Warnings and errors are raised as PHP warnings, which Laravel turns into exceptions, as in
 * classic mode. Notices and info, such as unreferenced components being removed, go to Laravel's
 * log instead, so they do not stop the build. Debug messages are dropped.
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

        if (in_array($level, [LogLevel::NOTICE, LogLevel::INFO], true)) {
            logger()->log($level, (string) $message, $context);

            return;
        }

        trigger_error((string) $message, E_USER_WARNING);
    }
}

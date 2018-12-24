<?php declare(strict_types=1);

use Bref\Runtime\LambdaRuntime;

/**
 * Shortcut for creating and running a simple lambda application.
 *
 * @param callable $handler This callable takes a $event parameter (array) and must return anything serializable to JSON.
 */
function λ(callable $handler): void
{
    lambda($handler);
}

/**
 * Create and run a simple lambda application.
 *
 * @param callable $handler This callable takes a $event parameter (array) and must return anything serializable to JSON.
 */
function lambda(callable $handler): void
{
    $lambdaRuntime = LambdaRuntime::fromEnvironmentVariable();
    $lambdaRuntime->processNextEvent($handler);
}

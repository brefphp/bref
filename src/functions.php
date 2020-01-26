<?php declare(strict_types=1);

/**
 * Define and run a simple lambda function.
 *
 * @param callable $handler This callable takes a $event parameter (array) and must return anything serializable to JSON.
 *
 * @deprecated This function is now deprecated. Instead, return the function from your file like in the example below:
 *
 *     <?php
 *
 *     return function (array $event) {
 *         return 'Hello ' . $event['name'];
 *     };
 */
function lambda(callable $handler): void
{
    $lambdaRuntime = Bref\Runtime\LambdaRuntime::fromEnvironmentVariable();
    $lambdaRuntime->processNextEvent($handler);
    exit(0);
}

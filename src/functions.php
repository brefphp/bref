<?php declare(strict_types=1);

use Bref\Handler\Psr7Handler;

/**
 * Define and run a simple lambda function.
 *
 * @param callable $handler This callable takes a $event parameter (array) and must return anything serializable to JSON.
 *
 * Example:
 *
 *     lambda(function (array $event) {
 *         return 'Hello ' . $event['name'];
 *     });
 */
function lambda(callable $handler): void
{
    $lambdaRuntime = Bref\Runtime\LambdaRuntime::fromEnvironmentVariable();
    $lambdaRuntime->processNextEvent($handler);
}

/**
 * @param callable $handler This callable takes a $request parameter (ServerRequestInterface) and must return a
 *                          PSR-7 ResponseInterface
 *
 * @see \Psr\Http\Message\ServerRequestInterface
 * @see \Psr\Http\Message\ResponseInterface
 */
function lambda_psr7(callable $handler): void
{
    $psr7Handler = new Psr7Handler($handler);
    lambda($psr7Handler);
}

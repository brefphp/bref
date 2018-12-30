<?php declare(strict_types=1);

use Bref\Application;

/**
 * Shortcut for creating and running a simple lambda application.
 *
 * @param callable $handler This callable takes a $event parameter (array) and must return anything serializable to JSON.
 *
 * @see \Bref\Application::simpleHandler()
 */
function λ(callable $handler): void
{
    $app = new Application;
    $app->simpleHandler($handler);
    $app->run();
}

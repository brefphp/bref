<?php
declare(strict_types=1);

/**
 * Shortcut for creating and running a simple lambda application.
 *
 * @param callable $handler This callable takes a $event parameter (array) and must return anything serializable to JSON.
 *
 * @see \Bref\Application::simpleHandler()
 */
function λ(callable $handler)
{
    $app = new \Bref\Application;
    $app->simpleHandler($handler);
    $app->run();
}

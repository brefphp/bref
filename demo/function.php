<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

return function ($event) {
    if (isset($event['error'])) {
        throw new Exception('This is an error');
    }

    if (isset($event['huge'])) {
        // Create a 7MB response
        return str_repeat('a', 1024 * 1024 * 7);
    }

    // Overload the memory with a 3GB string
    if (isset($event['overload'])) {
        return str_repeat('a', 1024 * 1024 * 1024 * 3);
    }

    echo 'This is a log line' . PHP_EOL;

    return 'Hello ' . ($event['name'] ?? 'world');
};

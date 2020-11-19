<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

return function ($event) {
    echo 'This is a log line' . PHP_EOL;

    return 'Hello ' . ($event['name'] ?? 'world');
};

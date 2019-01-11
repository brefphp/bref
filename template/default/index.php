<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

lambda(function (array $event) {
    return 'Hello ' . ($event['name'] ?? 'world');
});

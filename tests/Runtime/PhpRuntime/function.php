<?php declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

ini_set('display_errors', '1');

lambda(function (array $event) {
    if ($event['extensions'] ?? false) {
        return get_loaded_extensions();
    }

    return 'Hello ' . ($event['name'] ?? 'world');
});

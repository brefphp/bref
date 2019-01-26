<?php declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

lambda(function (array $event) {
    if ($event['extensions'] ?? false) {
        return get_loaded_extensions();
    }

    if ($event['php-config'] ?? false) {
        return ini_get_all(null, false);
    }

    if ($event['error_log'] ?? false) {
        error_log('This is a test log from error_log');
    }

    return 'Hello ' . ($event['name'] ?? 'world');
});

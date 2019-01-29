<?php declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

lambda(function (array $event) {
    if ($event['extensions'] ?? false) {
        return get_loaded_extensions();
    }

    if ($event['php-config'] ?? false) {
        return ini_get_all(null, false);
    }

    if ($event['stdout'] ?? false) {
        echo 'This is a test log by writing to stdout';
    }

    if ($event['stderr'] ?? false) {
        echo 'This is a test log by writing to stderr';
    }

    if ($event['error_log'] ?? false) {
        error_log('This is a test log from error_log');
    }

    if ($event['exception'] ?? false) {
        throw new Exception('This is an uncaught exception');
    }

    if ($event['error'] ?? false) {
        strlen();
    }

    if ($event['fatal_error'] ?? false) {
        require 'foo';
    }

    if ($event['warning'] ?? false) {
        trigger_error('This is a test warning', E_USER_WARNING);
    }

    return 'Hello ' . ($event['name'] ?? 'world');
});

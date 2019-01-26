<?php declare(strict_types=1);

if ($_GET['extensions'] ?? false) {
    header('Content-Type: application/json');
    echo json_encode(get_loaded_extensions(), JSON_PRETTY_PRINT);
    return;
}

if ($_GET['php-config'] ?? false) {
    header('Content-Type: application/json');
    echo json_encode(ini_get_all(null, false), JSON_PRETTY_PRINT);
    return;
}

if ($_GET['stderr'] ?? false) {
    $stderr = fopen('php://stderr', 'a');
    fwrite($stderr, 'This is a test log into stderr');
    fclose($stderr);
}

if ($_GET['error_log'] ?? false) {
    error_log('This is a test log from error_log');
}

echo 'Hello ' . ($_GET['name'] ?? 'world!');

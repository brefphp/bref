<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

if (isset($_GET['sleep'])) {
    error_log('This is a log');
    sleep(10);
}

if (isset($_GET['img'])) {
    $fp = fopen('https://bref.sh/img/logo-small.png', 'rb');
    header('Content-Type: image/png');
    fpassthru($fp);
    exit(0);
}

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode(['Hello' => '🌍']);
    exit(0);
}

if (isset($_GET['weird'])) {
    header('Content-Type: foo/bar');
    echo 'Hello 🌍';
    exit(0);
}

if (isset($_GET['tmp'])) {
    file_put_contents('/tmp/test.txt', 'hello');
    echo file_get_contents('/tmp/test.txt');
    exit(0);
}

if (isset($_GET['huge'])) {
    // Create a 7MB response
    echo str_repeat('a', 1024 * 1024 * 7);
    exit(0);
}

echo 'Hello world!';

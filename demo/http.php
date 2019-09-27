<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

if (isset($_GET['sleep'])) {
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

if (isset($_GET['phpinfo'])) {
    phpinfo();
    exit(0);
}

echo 'Hello world!';

<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

if (isset($_GET['sleep'])) {
    sleep(10);
}

echo 'Hello world!';

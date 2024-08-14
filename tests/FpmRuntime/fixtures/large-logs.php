<?php declare(strict_types=1);

$stderr = fopen('php://stderr', 'wb');
$bytes = 512*512;
fwrite($stderr, str_repeat('x', $bytes));

echo 'Hello, world!';

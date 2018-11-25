<?php declare(strict_types=1);

$stderr = fopen('php://stderr', 'w+');
fwrite($stderr, 'Hello world!');
fclose($stderr);

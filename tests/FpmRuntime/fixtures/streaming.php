<?php declare(strict_types=1);

header('Content-Type: text/plain');
header('X-Custom-Header: streamed-value');
http_response_code(201);

// Remove the output buffer and flush incrementally so that PHP-FPM
// sends each chunk as soon as it is produced
ob_end_clean();
ob_implicit_flush(true);

echo 'chunk-1';
flush();
usleep(200000);

echo 'chunk-2';
flush();
usleep(200000);

echo 'chunk-3';
flush();

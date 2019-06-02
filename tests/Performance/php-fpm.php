<?php declare(strict_types=1);

/**
 * This script is used to profile the performances of the PHP-FPM bridge.
 *
 * The code can be profiled using https://blackfire.io to check for improvements
 * or performance regressions.
 *
 *     blackfire run php tests/Performance/php-fpm.php
 */

use Bref\Runtime\PhpFpm;

require_once __DIR__ . '/../../vendor/autoload.php';

$fpm = new PhpFpm(
    dirname(__DIR__) . '/Runtime/PhpFpm/large-response.php',
    dirname(__DIR__) . '/Runtime/PhpFpm/php-fpm.conf'
);
$fpm->start();

for ($i = 0; $i <= 1000; $i++) {
    $response = $fpm->proxy([
        'httpMethod' => 'GET',
        'path' => '/',
        'requestContext' => [
            'protocol' => 'HTTP/1.1',
        ],
    ]);
    if ($response->toApiGatewayFormat()['statusCode'] !== 200) {
        die('Error');
    }
    echo '.';
}

echo PHP_EOL;

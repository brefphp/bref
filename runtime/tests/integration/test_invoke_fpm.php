<?php declare(strict_types=1);

require 'test_invoke.php';

$request = [
    'BREF_HTTP_METHOD' => 'GET',
];

$endpoint = 'http://php-fpm:8080/2015-03-31/functions/function/invocations';

$response = post($endpoint, $request);

$response = json_decode($response);

if ($response->body !== 'Hello from Bref FPM!') {
    throw new Exception('Unexpected response: ' . json_encode($response));
}

echo "\033[36m [Integration] Invocation response validated!\033[0m" . PHP_EOL;


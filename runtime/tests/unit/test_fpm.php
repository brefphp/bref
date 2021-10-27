<?php declare(strict_types=1);

$versions = [
    '8.0.12',
    '7.4.25'
];

$provider = [
    "Unexpected PHP Version: " . PHP_VERSION => in_array(PHP_VERSION, $versions),
    "cURL extension was not loaded" => function_exists('curl_init'),
    "json extension was not loaded" => json_encode(['json' => 'bref']) === '{"json":"bref"}',
    "posix extension was not loaded" => function_exists('posix_getpgid'),
];

foreach ($provider as $message => $test) {
    if (! $test) {
        throw new Exception($message);
    }
}

echo "\033[36m [Unit] " . count($provider) . " assertions performed!\033[0m" . PHP_EOL;
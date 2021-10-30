<?php declare(strict_types=1);

$versions = [
    '7.4.25',
    '8.0.12',
    '8.1.0RC5',
];

if (! in_array(PHP_VERSION, $versions)) {
    throw new Exception('Unexpected PHP Version: ' . PHP_VERSION);
}

$provider = [
    'cURL' => function_exists('curl_init'),
    'json' => json_encode(['json' => 'bref']) === '{"json":"bref"}',
    'filter_var' => filter_var('bref@bref.com', FILTER_VALIDATE_EMAIL),
    'hash' => hash('md5', 'Bref') === 'df4647d91c4a054af655c8eea2bce541',
    'libxml' => function_exists('libxml_clear_errors'),
];

foreach ($provider as $extension => $test) {
    if (! $test) {
        throw new Exception($extension . ' extension was not loaded');
    }
}

echo "\033[36m [Unit] " . count($provider) . " assertions performed!\033[0m" . PHP_EOL;
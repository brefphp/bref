<?php declare(strict_types=1);

$outputFile = getenv('TMP_DIRECTORY') . '/output.json';

// The lambda event is passed as a JSON encoded object in the process argument
$event = $argv[1];

file_put_contents($outputFile, json_encode([
    'hello' => json_decode($event['key'], true),
]));

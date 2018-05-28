<?php

$outputFile = getenv('TMP_DIRECTORY') . '/output.json';

file_put_contents($outputFile, json_encode([
    'hello' => 'world',
]));

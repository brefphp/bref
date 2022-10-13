<?php declare(strict_types=1);

header('Content-Type: application/json');

header('X-MultiValue: foo');
header('X-MultiValue: bar', false);

echo json_encode([
    'data' => 'Hello world!',
]);

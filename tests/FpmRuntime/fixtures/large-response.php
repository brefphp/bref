<?php declare(strict_types=1);

header('Content-Type: application/json');

echo file_get_contents(__DIR__ . '/big-json.json');

<?php declare(strict_types=1);

$version = $_SERVER['argv'][1];

if (PHP_VERSION !== $version) {
    throw new Exception("Expected version [$version] does not match " . PHP_VERSION);
}

echo "\033[36m [Version] $version ✓!\033[0m" . PHP_EOL;
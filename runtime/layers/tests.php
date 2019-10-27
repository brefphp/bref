<?php /** @noinspection ALL */
declare(strict_types=1);

/**
 * This file runs tests on Docker images.
 */

// All layers
$allLayers = [
    'bref/php-72',
    'bref/php-73',
    'bref/php-74',
    'bref/php-72-fpm',
    'bref/php-73-fpm',
    'bref/php-74-fpm',
    'bref/php-72-fpm-dev',
    'bref/php-73-fpm-dev',
    'bref/php-74-fpm-dev',
];
foreach ($allLayers as $layer) {
    // Working directory
    $workdir = trim(`docker run --rm --entrypoint pwd $layer`);
    assertEquals('/var/task', $workdir);
    echo '.';

    // PHP runs correctly
    $phpVersion = trim(`docker run --rm --entrypoint php $layer -v`);
    assertContains('PHP 7.', $phpVersion);
    echo '.';
}

// FPM layers
$fpmLayers = [
    'bref/php-72-fpm',
    'bref/php-73-fpm',
    'bref/php-74-fpm',
    'bref/php-72-fpm-dev',
    'bref/php-73-fpm-dev',
    'bref/php-74-fpm-dev',
];
foreach ($fpmLayers as $layer) {
    // PHP-FPM is installed
    $phpVersion = trim(`docker run --rm --entrypoint php-fpm $layer -v`);
    assertContains('PHP 7.', $phpVersion);
    echo '.';
}

echo "\nTests passed\n";

function assertEquals($expected, $actual)
{
    if ($expected !== $actual) {
        throw new Exception("$actual is not equal to expected $expected");
    }
}

function assertContains(string $expected, string $actual)
{
    if (strpos($actual, $expected) === false) {
        throw new Exception("$actual does not contain $expected");
    }
}

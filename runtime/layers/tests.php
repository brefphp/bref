<?php /** @noinspection ALL */
declare(strict_types=1);

/**
 * This file runs tests on Docker images.
 */

// All layers
$allLayers = [
    'bref/php-72',
    'bref/php-73',
    'bref/php-72-fpm',
    'bref/php-73-fpm',
    'bref/php-72-fpm-dev',
    'bref/php-73-fpm-dev',
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

    exec("docker run --rm -v \${PWD}/helpers:/var/task/ --entrypoint /var/task/extensions-test.sh $layer", $output, $exitCode);
    if ($exitCode !== 0) {
        throw new Exception(implode(PHP_EOL, $output), $exitCode);
    }
    echo '.';
}

// FPM layers
$fpmLayers = [
    'bref/php-72-fpm',
    'bref/php-73-fpm',
    'bref/php-72-fpm-dev',
    'bref/php-73-fpm-dev',
];
foreach ($fpmLayers as $layer) {
    // PHP-FPM is installed
    $phpVersion = trim(`docker run --rm --entrypoint php-fpm $layer -v`);
    assertContains('PHP 7.', $phpVersion);
    echo '.';
}

// dev layers
$devLayers = [
    'bref/php-72-fpm-dev',
    'bref/php-73-fpm-dev',
];
$devExtensions = [
    'xdebug',
    'blackfire',
];
foreach ($devLayers as $layer) {
    exec("docker run --rm -v \${PWD}/helpers:/var/task/ --entrypoint php $layer -m", $output, $exitCode);
    $notLoaded = array_diff($devExtensions, $output);
    // all development extensions are loaded
    if ($exitCode !== 0 || count($notLoaded) > 0) {
        throw new Exception(implode(PHP_EOL, array_map(function ($extension) { return "Extension $extension is not loaded"; }, $notLoaded)), $exitCode);
    }
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

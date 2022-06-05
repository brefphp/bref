<?php /** @noinspection ALL */
declare(strict_types=1);

/**
 * This file runs tests on Docker images.
 */

// All layers
$allLayers = [
    'bref/php-73',
    'bref/php-74',
    'bref/php-80',
    'bref/php-81',
    'bref/php-73-fpm',
    'bref/php-74-fpm',
    'bref/php-80-fpm',
    'bref/php-81-fpm',
    'bref/php-80-swoole',
    'bref/php-81-swoole',
    'bref/php-80-roadrunner',
    'bref/php-81-roadrunner',
    'bref/php-73-fpm-dev',
    'bref/php-74-fpm-dev',
    'bref/php-80-fpm-dev',
    'bref/php-81-fpm-dev',
];
foreach ($allLayers as $layer) {
    // Working directory
    $workdir = trim(`docker run --rm --entrypoint pwd $layer`);
    assertEquals('/var/task', $workdir);
    echo '.';

    // PHP runs correctly
    $phpVersion = trim(`docker run --rm --entrypoint php $layer -v`);
    assertMatchesRegex('/PHP (7|8)\.\d+\.\d+/', $phpVersion);
    echo '.';

    // Test extensions load correctly
    // Skip this for PHP 8.0 and 8.1 until all extensions are supported
    if (strpos($layer, 'php-8') === false) {
        exec("docker run --rm -v \${PWD}/helpers:/var/task/ --entrypoint /var/task/extensions-test.sh $layer", $output, $exitCode);
        if ($exitCode !== 0) {
            throw new Exception(implode(PHP_EOL, $output), $exitCode);
        }
        echo '.';
    }
}

// FPM layers
$fpmLayers = [
    'bref/php-73-fpm',
    'bref/php-74-fpm',
    'bref/php-80-fpm',
    'bref/php-81-fpm',
    'bref/php-73-fpm-dev',
    'bref/php-74-fpm-dev',
    'bref/php-80-fpm-dev',
    'bref/php-81-fpm-dev',
];
foreach ($fpmLayers as $layer) {
    // PHP-FPM is installed
    $phpVersion = trim(`docker run --rm --entrypoint php-fpm $layer -v`);
    assertMatchesRegex('/PHP (7|8)\.\d+\.\d+/', $phpVersion);
    echo '.';
}

// Roadrunner layers
$roadRunnerLayers = [
    'bref/php-80-roadrunner',
    'bref/php-81-roadrunner',
    'bref/php-80-roadrunner-dev',
    'bref/php-81-roadrunner-dev',
];
foreach ($roadRunnerLayers as $layer) {
    // Roadrunner binary is installed
    $roadRunnerVersion = trim(`docker run --rm --entrypoint rr $layer -v`);
    assertMatchesRegex('/rr version 2\.\d+\.\d+/', $roadRunnerVersion);
    echo '.';
}

// Swoole layers
$swooleLayers = [
    'bref/php-80-swoole',
    'bref/php-81-swoole',
    'bref/php-80-swoole-dev',
    'bref/php-81-swoole-dev',
];
foreach ($swooleLayers as $layer) {
    // Swoole Extension is installed
    $swooleExt = trim(`docker run --rm --entrypoint php $layer -m | grep swoole`);
    assertMatchesRegex('/swoole/', $swooleExt);
    echo '.';
}

// dev layers
$devLayers = [
    'bref/php-73-fpm-dev',
    'bref/php-74-fpm-dev',
    'bref/php-80-fpm-dev',
    'bref/php-81-fpm-dev',
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
        throw new Exception(implode(PHP_EOL, array_map(function ($extension) {
            return "Extension $extension is not loaded";
        }, $notLoaded)), $exitCode);
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

function assertMatchesRegex(string $expected, string $actual)
{
    if (preg_match($expected, $actual) === false) {
        throw new Exception("$actual does not match regex $expected");
    }
}

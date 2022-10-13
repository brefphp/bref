<?php declare(strict_types=1);

use Bref\FpmRuntime\FpmHandler;
use Bref\Runtime\LambdaRuntime;

ini_set('display_errors', '1');
error_reporting(E_ALL);

// A wrapper script is configured
// See https://docs.aws.amazon.com/lambda/latest/dg/runtimes-modify.html#runtime-wrapper
// NOTE: EXPERIMENTAL FEATURE, DO NOT USE!!!
// Note: If you do use it, open an issue or GitHub discussion or Slack thread
// and let us know why it's useful to you, we might turn it into an official feature
if (getenv('EXPERIMENTAL_AWS_LAMBDA_EXEC_WRAPPER')) {
    $cmd = escapeshellcmd(getenv('EXPERIMENTAL_AWS_LAMBDA_EXEC_WRAPPER'));
    $args = implode(' ', array_map('escapeshellarg', $argv));
    passthru("$cmd $args", $exitCode);
    exit($exitCode);
}

/** @noinspection PhpIncludeInspection */
require_once __DIR__ . '/../../autoload.php';

$lambdaRuntime = LambdaRuntime::fromEnvironmentVariable('fpm');

$appRoot = getenv('LAMBDA_TASK_ROOT');
$handlerFile = $appRoot . '/' . getenv('_HANDLER');
if (! is_file($handlerFile)) {
    $lambdaRuntime->failInitialization("Handler `$handlerFile` doesn't exist");
}

$phpFpm = new FpmHandler($handlerFile);
try {
    $phpFpm->start();
} catch (\Throwable $e) {
    $lambdaRuntime->failInitialization('Error while starting PHP-FPM', $e);
}

while (true) {
    $lambdaRuntime->processNextEvent($phpFpm);
}

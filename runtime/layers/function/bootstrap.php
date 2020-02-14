<?php declare(strict_types=1);

use Bref\Runtime\LambdaRuntime;

ini_set('display_errors', '1');
error_reporting(E_ALL);

$appRoot = getenv('LAMBDA_TASK_ROOT');

if (getenv('BREF_AUTOLOAD_PATH')) {
    /** @noinspection PhpIncludeInspection */
    require getenv('BREF_AUTOLOAD_PATH');
} else {
    /** @noinspection PhpIncludeInspection */
    require $appRoot . '/vendor/autoload.php';
}

$lambdaRuntime = LambdaRuntime::fromEnvironmentVariable();

$handlerFile = $appRoot . '/' . getenv('_HANDLER');
if (! is_file($handlerFile)) {
    $lambdaRuntime->failInitialization("Handler `$handlerFile` doesn't exist");
}

/** @noinspection PhpIncludeInspection */
$handler = require $handlerFile;

if (! $handler) {
    $lambdaRuntime->failInitialization("Handler `$handlerFile` must return a function or object handler. See https://bref.sh/docs/runtimes/function.html");
}

$loopMax = getenv('BREF_LOOP_MAX') ?: 1;
$loops = 0;
while (true) {
    if (++$loops > $loopMax) {
        exit(0);
    }
    $lambdaRuntime->processNextEvent($handler);
}

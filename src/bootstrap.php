<?php declare(strict_types=1);

use Bref\Event\Http\FpmHandler;
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

$appRoot = getenv('LAMBDA_TASK_ROOT');

if (getenv('BREF_DOWNLOAD_VENDOR')) {
    if(! file_exists('/tmp/vendor') || ! file_exists('/tmp/vendor/autoload.php')) {
        require_once __DIR__ . '/breftoolbox.php';

        \Bref\ToolBox\BrefToolBox::downloadAndConfigureVendor();
    }

    require '/tmp/vendor/autoload.php';
} elseif (getenv('BREF_AUTOLOAD_PATH')) {
    /** @noinspection PhpIncludeInspection */
    require getenv('BREF_AUTOLOAD_PATH');
} else {
    /** @noinspection PhpIncludeInspection */
    require $appRoot . '/vendor/autoload.php';
}

$lambdaRuntime = LambdaRuntime::fromEnvironmentVariable('fpm');

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

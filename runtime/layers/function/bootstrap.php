<?php declare(strict_types=1);

use Bref\Bref;
use Bref\Runtime\LambdaRuntime;

ini_set('display_errors', '1');
error_reporting(E_ALL);

$appRoot = getenv('LAMBDA_TASK_ROOT');

if (getenv('BREF_DOWNLOAD_VENDOR')) {
    if(! file_exists('/tmp/vendor') || ! file_exists('/tmp/vendor/autoload.php')) {
        require_once '/opt/bref/breftoolbox.php';

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

$customBootstrap = $appRoot . '/lambda_bootstrap.php';
if (file_exists($customBootstrap)) {
    /** @noinspection PhpIncludeInspection */
    require_once $customBootstrap;
}

$lambdaRuntime = LambdaRuntime::fromEnvironmentVariable();

$container = Bref::getContainer();

try {
    $handler = $container->get(getenv('_HANDLER'));
} catch (Throwable $e) {
    $lambdaRuntime->failInitialization($e->getMessage());
}

$loopMax = getenv('BREF_LOOP_MAX') ?: 1;
$loops = 0;
while (true) {
    if (++$loops > $loopMax) {
        exit(0);
    }
    $lambdaRuntime->processNextEvent($handler);
}

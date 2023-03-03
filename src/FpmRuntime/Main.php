<?php declare(strict_types=1);

namespace Bref\FpmRuntime;

use Bref\Bref;
use Bref\LazySecretsLoader;
use Bref\Runtime\LambdaRuntime;
use Throwable;

/**
 * @internal
 */
class Main
{
    public static function run(): void
    {
        // In the FPM runtime process (our process) we want to log all errors and warnings
        ini_set('display_errors', '1');
        error_reporting(E_ALL);

        LazySecretsLoader::loadSecretEnvironmentVariables();

        Bref::triggerHooks('beforeStartup');

        $lambdaRuntime = LambdaRuntime::fromEnvironmentVariable('fpm');

        $appRoot = getenv('LAMBDA_TASK_ROOT');
        $handlerFile = $appRoot . '/' . getenv('_HANDLER');
        if (! is_file($handlerFile)) {
            $lambdaRuntime->failInitialization("Handler `$handlerFile` doesn't exist");
        }

        $phpFpm = new FpmHandler($handlerFile);
        try {
            $phpFpm->start();
        } catch (Throwable $e) {
            $lambdaRuntime->failInitialization('Error while starting PHP-FPM', $e);
        }

        /** @phpstan-ignore-next-line */
        while (true) {
            $lambdaRuntime->processNextEvent($phpFpm);
        }
    }
}

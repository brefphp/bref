<?php declare(strict_types=1);

namespace Bref\FunctionRuntime;

use Bref\Bref;
use Bref\Context\Context;
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
        LazySecretsLoader::loadSecretEnvironmentVariables();

        Bref::triggerHooks('beforeStartup');

        $lambdaRuntime = LambdaRuntime::fromEnvironmentVariable('function');

        $container = Bref::getContainer();

        try {
            $handler = $container->get(getenv('_HANDLER'));
        } catch (Throwable $e) {
            $lambdaRuntime->failInitialization($e, 'Runtime.NoSuchHandler');
        }

        $loopMax = getenv('BREF_LOOP_MAX') ?: 1;
        $loops = 0;
        while (true) {
            if (++$loops > $loopMax) {
                exit(0);
            }

            $success = $lambdaRuntime->processNextEvent(function ($event, Context $context) use ($handler) {
                // Expose the context in an environment variable
                // Used for example to retrieve the context in Laravel Queues jobs
                $jsonContext = json_encode($context, JSON_THROW_ON_ERROR);
                $_SERVER['LAMBDA_INVOCATION_CONTEXT'] = $_ENV['LAMBDA_INVOCATION_CONTEXT'] = $jsonContext;
                putenv("LAMBDA_INVOCATION_CONTEXT=$jsonContext");

                return $handler($event, $context);
            });

            // In case the execution failed, we force starting a new process regardless of BREF_LOOP_MAX
            // Why: an exception could have left the application in a non-clean state, this is preventive
            if (! $success) {
                exit(0);
            }
        }
    }
}

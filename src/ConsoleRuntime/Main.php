<?php declare(strict_types=1);

namespace Bref\ConsoleRuntime;

use Bref\Bref;
use Bref\Context\Context;
use Bref\LazySecretsLoader;
use Bref\Runtime\ColdStartTracker;
use Bref\Runtime\LambdaRuntime;
use Bref\Runtime\StreamedResponse;
use Generator;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
class Main
{
    public static function run(): void
    {
        ColdStartTracker::init();

        LazySecretsLoader::loadSecretEnvironmentVariables();

        Bref::events()->beforeStartup();

        $lambdaRuntime = LambdaRuntime::fromEnvironmentVariable('console');

        $appRoot = getenv('LAMBDA_TASK_ROOT');
        $handlerFile = $appRoot . '/' . getenv('_HANDLER');
        if (! is_file($handlerFile)) {
            $lambdaRuntime->failInitialization("Handler `$handlerFile` doesn't exist", 'Runtime.NoSuchHandler');
        }

        Bref::events()->afterStartup();

        ColdStartTracker::coldStartFinished();

        /** @phpstan-ignore-next-line */
        while (true) {
            $lambdaRuntime->processNextEvent(function ($event, Context $context) use ($handlerFile): StreamedResponse {
                if (is_array($event)) {
                    // Backward compatibility with the former CLI invocation format
                    $cliOptions = $event['cli'] ?? '';
                } elseif (is_string($event)) {
                    $cliOptions = $event;
                } else {
                    $cliOptions = '';
                }

                $timeout = max(1, $context->getRemainingTimeInMillis() / 1000 - 1);
                $command = sprintf('php %s %s 2>&1', $handlerFile, $cliOptions);
                $process = Process::fromShellCommandline($command, null, null, null, $timeout);

                // The output is sent as the command writes it (InvokeWithResponseStream shows it live)
                $output = function () use ($process): Generator {
                    $process->start();
                    foreach ($process as $chunk) {
                        echo $chunk;
                        yield $chunk;
                    }
                    if ($process->getExitCode() > 0) {
                        // This needs to be thrown so that AWS Lambda knows the invocation failed
                        // (e.g. important for error rates in CloudWatch)
                        throw new CommandFailed("The command exited with code {$process->getExitCode()}");
                    }
                };

                return new StreamedResponse('text/plain', $output());
            });
        }
    }
}

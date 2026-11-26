<?php declare(strict_types=1);

namespace Bref\ConsoleRuntime;

use Bref\Bref;
use Bref\Context\Context;
use Bref\LazySecretsLoader;
use Bref\Runtime\ColdStartTracker;
use Bref\Runtime\InvocationResult;
use Bref\Runtime\LambdaRuntime;
use Bref\Runtime\ResponseStream;
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
            $lambdaRuntime->processNextEvent(function ($event, Context $context) use ($handlerFile): array|InvocationResult {
                // The event is the command: `"<arguments>"`, or `{"cli": "<arguments>", "stream": true}` to stream the output
                $stream = false;
                if (is_array($event)) {
                    $cliOptions = $event['cli'] ?? '';
                    $stream = ($event['stream'] ?? false) === true;
                } elseif (is_string($event)) {
                    $cliOptions = $event;
                } else {
                    $cliOptions = '';
                }

                $timeout = max(1, $context->getRemainingTimeInMillis() / 1000 - 1);
                $command = sprintf('php %s %s 2>&1', $handlerFile, $cliOptions);
                $process = Process::fromShellCommandline($command, null, null, null, $timeout);

                return $stream ? self::streamOutput($process) : self::returnOutput($process);
            });
        }
    }

    /**
     * The output, sent as the command writes it, for clients using InvokeWithResponseStream (e.g. `serverless bref:cli`).
     */
    private static function streamOutput(Process $process): InvocationResult
    {
        $output = function (ResponseStream $response) use ($process): void {
            $process->run(function ($type, string $buffer) use ($process, $response): void {
                echo $buffer;
                // The command keeps running if the output can no longer be sent: it is in the logs
                $response->write($buffer);
                // Sent and logged: no need to keep it in memory
                $process->clearOutput();
            });

            if ($process->getExitCode() > 0) {
                throw new CommandFailed("The command exited with code {$process->getExitCode()}");
            }
        };

        // Started right away: every failure is in the trailers, which InvokeWithResponseStream reports and counts
        return new InvocationResult('text/plain', $output, failuresInTrailer: true);
    }

    /**
     * The output, returned at the end of the command.
     *
     * The default: the Invoke API and asynchronous invocations (e.g. crons) ignore the errors of streamed responses,
     * a failure must go through the error endpoint to be reported and counted in the metrics.
     *
     * Returned as an array, not as an InvocationResult: `afterInvoke()` subscribers read it.
     *
     * @return array{exitCode: int|null, output: string}
     */
    private static function returnOutput(Process $process): array
    {
        $process->run(function ($type, $buffer): void {
            echo $buffer;
        });

        $exitCode = $process->getExitCode();

        $output = $process->getOutput();
        // Trim the output to stay under the 6MB limit for AWS Lambda
        // We only keep 5MB because at this point the difference won't be important
        // and we'll serialize the output to JSON which will add some overhead
        $output = substr($output, max(0, strlen($output) - 5 * 1024 * 1024));
        // The output is returned as JSON, which cannot hold invalid UTF-8 (e.g. binary output,
        // or a character cut in half above): replace it (with "?") to never fail the invocation because of it
        $output = mb_scrub($output, 'UTF-8');

        if ($exitCode > 0) {
            // This needs to be thrown so that AWS Lambda knows the invocation failed
            // (e.g. important for error rates in CloudWatch)
            throw new CommandFailed($output);
        }

        return [
            'exitCode' => $exitCode, // will always be 0
            'output' => $output,
        ];
    }
}

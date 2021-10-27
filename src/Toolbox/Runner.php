<?php declare(strict_types=1);

namespace Bref\Toolbox;

use Bref\Bref;
use Bref\Context\Context;
use Bref\Event\Http\FpmHandler;
use Bref\Runtime\LambdaRuntime;
use Exception;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

final class Runner
{
    /** @var string */
    private $layer;

    /** @var string */
    private $appRoot;

    /** @var string */
    private $handler;

    /** @var array */
    private $environment;

    public function __construct(string $layer, array $environment)
    {
        $this->layer = $layer;
        $this->environment = $environment;
        $this->appRoot = $environment['LAMBDA_TASK_ROOT'];
        $this->handler = $environment['_HANDLER'];
    }

    public function run()
    {
        ini_set('display_errors', '1');

        error_reporting(E_ALL);

        $this->autoload();

        $lambdaRuntime = new LambdaRuntime($this->environment['AWS_LAMBDA_RUNTIME_API'], $this->layer);

        if ($this->layer === 'inline') {
            $this->processInline($lambdaRuntime);
        } elseif ($this->layer === 'function') {
            $this->processFunction($lambdaRuntime);
        } elseif ($this->layer === 'fpm') {
            $this->processFpm($lambdaRuntime);
        } elseif ($this->layer === 'console') {
            $this->processConsole($lambdaRuntime);
        }

        throw new RuntimeException("Unexpected intializer [$this->layer]");
    }

    private function autoload()
    {
        if ($this->environment['BREF_DOWNLOAD_VENDOR']) {
            if(! file_exists('/tmp/vendor') || ! file_exists('/tmp/vendor/autoload.php')) {
                $this->require('/Toolbox/VendorAutoloader.php');

                VendorDownloader::downloadAndConfigureVendor($this->environment);
            }

            require '/tmp/vendor/autoload.php';
        } elseif ($this->environment['BREF_AUTOLOAD_PATH']) {
            /** @noinspection PhpIncludeInspection */
            require $this->environment['BREF_AUTOLOAD_PATH'];
        } elseif (is_file($this->appRoot . '/vendor/autoload.php')) {
            /** @noinspection PhpIncludeInspection */
            require $this->appRoot . '/vendor/autoload.php';
        } else {
            if ($this->layer === 'fpm' || $this->layer === 'console') {
                throw new RuntimeException('Composer Autoload could not be found.');
            }

            $this->require('/Context/Context.php');
            $this->require('/Context/ContextBuilder.php');

            // @TODO: '/Event/*'

            $this->require('/Runtime/LambdaRuntime.php');
            $this->require('/Runtime/Invoker.php');

            $this->layer = 'inline';
        }
    }

    private function processInline(LambdaRuntime $runtime)
    {
        $loopMax = $this->environment['BREF_LOOP_MAX'] ?: 1;

        $loops = 0;

        $callable = require $this->handler;

        while (true) {
            if (++$loops > $loopMax) {
                exit(0);
            }

            $success = $runtime->processNextEvent($callable);
            // In case the execution failed, we force starting a new process regardless of BREF_LOOP_MAX
            // Why: an exception could have left the application in a non-clean state, this is preventive
            if (! $success) {
                exit(0);
            }
        }
    }

    private function processFunction(LambdaRuntime $runtime)
    {
        $container = Bref::getContainer();

        try {
            $handler = $container->get($this->handler);
        } catch (Throwable $e) {
            $runtime->failInitialization($e->getMessage());

            exit(1);
        }

        $loopMax = $this->environment['BREF_LOOP_MAX'] ?: 1;

        $loops = 0;

        while (true) {
            if (++$loops > $loopMax) {
                exit(0);
            }

            $success = $runtime->processNextEvent($handler);
            // In case the execution failed, we force starting a new process regardless of BREF_LOOP_MAX
            // Why: an exception could have left the application in a non-clean state, this is preventive
            if (! $success) {
                exit(0);
            }
        }
    }

    private function processFpm(LambdaRuntime $runtime)
    {
        $handlerFile = $this->appRoot . '/' . $this->handler;

        if (! is_file($handlerFile)) {
            $runtime->failInitialization("Handler `$handlerFile` doesn't exist");

            exit(1);
        }

        $phpFpm = new FpmHandler($handlerFile);

        try {
            $phpFpm->start();
        } catch (Throwable $e) {
            $runtime->failInitialization('Error while starting PHP-FPM', $e);

            exit(1);
        }

        while (true) {
            $runtime->processNextEvent($phpFpm);
        }
    }

    private function processConsole(LambdaRuntime $runtime)
    {
        $handlerFile = $this->appRoot . '/' . $this->handler;

        if (! is_file($handlerFile)) {
            $runtime->failInitialization("Handler `$handlerFile` doesn't exist");
        }

        while (true) {
            $runtime->processNextEvent(function ($event, Context $context) use ($handlerFile): array {
                if (is_array($event)) {
                    // Backward compatibility with the former CLI invocation format
                    $cliOptions = $event['cli'] ?? '';
                } elseif (is_string($event)) {
                    $cliOptions = $event;
                } else {
                    $cliOptions = '';
                }

                $timeout = max(1, $context->getRemainingTimeInMillis() / 1000 - 1);
                $command = sprintf('/opt/bin/php %s %s 2>&1', $handlerFile, $cliOptions);
                $process = Process::fromShellCommandline($command, null, ['LAMBDA_INVOCATION_CONTEXT' => json_encode($context)], null, $timeout);

                $process->run(function ($type, $buffer) {
                    echo $buffer;
                });

                $exitCode = $process->getExitCode();

                if ($exitCode > 0) {
                    throw new Exception('The command exited with a non-zero status code: ' . $exitCode);
                }

                return [
                    'exitCode' => $exitCode, // will always be 0
                    'output' => $process->getOutput(),
                ];
            });
        }
    }

    private function require(string $path)
    {
        require '/opt/bref-src' . $path;
    }
}

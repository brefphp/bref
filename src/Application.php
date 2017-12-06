<?php
declare(strict_types=1);

namespace PhpLambda;

use Interop\Http\Server\RequestHandlerInterface;
use PhpLambda\Bridge\Psr7\RequestFactory;
use PhpLambda\Cli\WelcomeApplication;
use PhpLambda\Http\WelcomeHandler;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Application
{
    private const LAMBDA_DIRECTORY = '/tmp/.phplambda';
    private const INPUT_FILE_NAME = self::LAMBDA_DIRECTORY . '/input.json';
    private const OUTPUT_FILE_NAME = self::LAMBDA_DIRECTORY . '/output.json';

    /**
     * @var callable
     */
    private $simpleHandler;

    /**
     * @var RequestHandlerInterface
     */
    private $httpHandler;

    /**
     * @var \Symfony\Component\Console\Application
     */
    private $cliHandler;

    public function __construct()
    {
        $this->simpleHandler(function () {
            return 'Welcome to PHPLambda! Define your handler using $application->simpleHandler()';
        });
        $this->httpHandler(new WelcomeHandler);
        $this->cliHandler(new WelcomeApplication);
    }

    /**
     * Set the handler that will handle simple invocations of the lambda (through `serverless invoke`).
     *
     * @param callable $handler This callable takes a $event parameter (array) and must return anything serializable to JSON.
     */
    public function simpleHandler(callable $handler) : void
    {
        $this->simpleHandler = $handler;
    }

    /**
     * Set the handler that will handle HTTP requests.
     */
    public function httpHandler(RequestHandlerInterface $handler) : void
    {
        $this->httpHandler = $handler;
    }

    /**
     * Set the handler that will handle CLI requests.
     */
    public function cliHandler(\Symfony\Component\Console\Application $console) : void
    {
        // Necessary to avoid any `exit()` call :)
        $console->setAutoExit(false);

        $this->cliHandler = $console;
    }

    /**
     * Run the application.
     */
    public function run() : void
    {
        if (!$this->isRunningInLambda()) {
            if (php_sapi_name() == "cli") {
                $this->cliHandler->setAutoExit(true);
                $this->cliHandler->run();
            } else {
                $request = ServerRequestFactory::fromGlobals();
                $response = $this->httpHandler->handle($request);
                (new SapiEmitter)->emit($response);
            }
            return;
        }

        $this->ensureTempDirectoryExists();

        $event = $this->readLambdaEvent();

        // Run the appropriate handler
        if (isset($event['httpMethod'])) {
            // HTTP request
            $request = (new RequestFactory)->fromLambdaEvent($event);
            $response = $this->httpHandler->handle($request);
            $output = LambdaResponse::fromPsr7Response($response)->toJson();
        } elseif (isset($event['cli'])) {
            // CLI command
            $cliInput = new StringInput($event['cli']);
            $cliOutput = new BufferedOutput;
            $exitCode = $this->cliHandler->run($cliInput, $cliOutput);
            $output = json_encode([
                'exitCode' => $exitCode,
                'output' => $cliOutput->fetch(),
            ]);
        } else {
            // Simple invocation
            $output = ($this->simpleHandler)($event);
            $output = json_encode($output);
        }

        $this->writeLambdaOutput($output);
    }

    private function ensureTempDirectoryExists() : void
    {
        $filesystem = new Filesystem;
        if (! $filesystem->exists(self::LAMBDA_DIRECTORY)) {
            $filesystem->mkdir(self::LAMBDA_DIRECTORY);
        }
    }

    private function readLambdaEvent() : array
    {
        $filesystem = new Filesystem;
        if ($filesystem->exists(self::INPUT_FILE_NAME)) {
            return (array) json_decode(file_get_contents(self::INPUT_FILE_NAME), true);
        }
        return [];
    }

    private function writeLambdaOutput(string $json) : void
    {
        file_put_contents(self::OUTPUT_FILE_NAME, $json);
    }

    private function isRunningInLambda() : bool
    {
        return getenv('LAMBDA_TASK_ROOT') !== false;
    }
}

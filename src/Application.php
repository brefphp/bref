<?php declare(strict_types=1);

namespace Bref;

use Bref\Bridge\Psr7\RequestFactory;
use Bref\Cli\InvokeCommand;
use Bref\Cli\WelcomeApplication;
use Bref\Http\LambdaResponse;
use Bref\Http\WelcomeHandler;
use Bref\Runtime\LambdaRuntime;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

class Application
{
    /** @var callable */
    private $simpleHandler;

    /** @var RequestHandlerInterface */
    private $httpHandler;

    /** @var \Symfony\Component\Console\Application */
    private $cliHandler;

    public function __construct()
    {
        // Define default "demo" handlers
        $this->simpleHandler(function () {
            return 'Welcome to Bref! Define your handler using $application->simpleHandler()';
        });
        $this->httpHandler(new WelcomeHandler);
        $this->cliHandler(new WelcomeApplication);
    }

    /**
     * Set the handler that will handle simple invocations of the lambda
     * (through `serverless invoke` for example).
     *
     * @param callable $handler This callable takes a $event parameter (array) and must return anything serializable to JSON.
     */
    public function simpleHandler(callable $handler): void
    {
        $this->simpleHandler = $handler;
    }

    /**
     * Set the handler that will handle HTTP requests.
     *
     * The handler must be a PSR-15 request handler, it can be any
     * framework that is compatible with PSR-15 for example.
     *
     * @deprecated Replaced by PHP-FPM
     */
    public function httpHandler(RequestHandlerInterface $handler): void
    {
        $this->httpHandler = $handler;
    }

    /**
     * Set the handler that will handle CLI requests.
     *
     * CLI requests are local invocations of `bref cli <command>`:
     * the command will be run in production remotely using this handler.
     *
     * The handler must be an instance of a Symfony Console application.
     * That can also be a Silly (https://github.com/mnapoli/silly) application
     * since Silly is based on the Symfony Console.
     */
    public function cliHandler(\Symfony\Component\Console\Application $console): void
    {
        // Necessary to avoid any `exit()` call :)
        $console->setAutoExit(false);

        $this->cliHandler = $console;

        // Always add our `bref:invoke` command to test the lambda locally
        $this->cliHandler->add(new InvokeCommand(function () {
            return $this->simpleHandler;
        }));
    }

    /**
     * Run the application.
     *
     * The application will detect how the lambda is being invoked (HTTP,
     * CLI, direct invocation, etc.) and execute the proper handler.
     */
    public function run(): void
    {
        if (! $this->isRunningInAwsLambda()) {
            if (PHP_SAPI === 'cli') {
                $this->cliHandler->setAutoExit(true);
                $this->cliHandler->run();
            } else {
                $request = ServerRequestFactory::fromGlobals();
                $response = $this->httpHandler->handle($request);
                (new SapiEmitter)->emit($response);
            }
            return;
        }

        $lambdaRuntime = LambdaRuntime::fromEnvironmentVariable();

        $invocation = $lambdaRuntime->waitNextInvocation();
        $event = $invocation->getEvent();

        try {
            // Run the appropriate handler
            if (isset($event['httpMethod'])) {
                // HTTP request
                $request = RequestFactory::fromLambdaEvent($event);
                $response = $this->httpHandler->handle($request);
                $output = LambdaResponse::fromPsr7Response($response)->toApiGatewayFormat();
            } elseif (isset($event['cli'])) {
                // CLI command
                $cliInput = new StringInput($event['cli']);
                $cliOutput = new BufferedOutput;
                $exitCode = $this->cliHandler->run($cliInput, $cliOutput);
                $output = [
                    'exitCode' => $exitCode,
                    'output' => $cliOutput->fetch(),
                ];
            } else {
                // Simple invocation
                $output = ($this->simpleHandler)($event);
            }

            $invocation->success($output);
        } catch (\Throwable $e) {
            $invocation->failure($e);
        }
    }

    private function isRunningInAwsLambda(): bool
    {
        // LAMBDA_TASK_ROOT is a constant defined by AWS
        // TODO: use a solution that would work with other hosts?
        return getenv('LAMBDA_TASK_ROOT') !== false;
    }
}

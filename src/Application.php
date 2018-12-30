<?php declare(strict_types=1);

namespace Bref;

use Bref\Bridge\Psr7\RequestFactory;
use Bref\Cli\InvokeCommand;
use Bref\Cli\WelcomeApplication;
use Bref\Http\LambdaResponse;
use Bref\Http\WelcomeHandler;
use Innmind\Json\Json;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

class Application
{
    /**
     * We should that directory to store the output file.
     * See `writeLambdaOutput()`.
     */
    private const BREF_DIRECTORY = '/tmp/.bref';
    private const OUTPUT_FILE_NAME = self::BREF_DIRECTORY . '/output.json';

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

        $this->ensureTempDirectoryExists();

        $event = $this->readLambdaEvent();

        // Run the appropriate handler
        if (isset($event['httpMethod'])) {
            // HTTP request
            $request = RequestFactory::fromLambdaEvent($event);
            $response = $this->httpHandler->handle($request);
            $output = LambdaResponse::fromPsr7Response($response)->toJson();
        } elseif (isset($event['cli'])) {
            // CLI command
            $cliInput = new StringInput($event['cli']);
            $cliOutput = new BufferedOutput;
            $exitCode = $this->cliHandler->run($cliInput, $cliOutput);
            $output = Json::encode([
                'exitCode' => $exitCode,
                'output' => $cliOutput->fetch(),
            ]);
        } else {
            // Simple invocation
            $output = ($this->simpleHandler)($event);
            $output = Json::encode($output);
        }

        $this->writeLambdaOutput($output);
    }

    private function ensureTempDirectoryExists(): void
    {
        $filesystem = new Filesystem;
        if (! $filesystem->exists(self::BREF_DIRECTORY)) {
            $filesystem->mkdir(self::BREF_DIRECTORY);
        }
    }

    private function readLambdaEvent(): array
    {
        // The lambda event is passed as JSON by `handler.js` as a CLI argument
        $argv = $_SERVER['argv'];
        return Json::decode($argv[1]) ?: [];
    }

    private function writeLambdaOutput(string $json): void
    {
        file_put_contents(self::OUTPUT_FILE_NAME, $json);
    }

    private function isRunningInAwsLambda(): bool
    {
        // LAMBDA_TASK_ROOT is a constant defined by AWS
        // TODO: use a solution that would work with other hosts?
        return getenv('LAMBDA_TASK_ROOT') !== false;
    }
}

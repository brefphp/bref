<?php
declare(strict_types=1);

namespace PhpLambda;

use PhpLambda\Http\WelcomeHandler;
use Symfony\Component\Filesystem\Filesystem;

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
     * @var WelcomeHandler
     */
    private $httpHandler;

    public function __construct()
    {
        $this->simpleHandler(function () {
            return 'Welcome to PHPLambda! Define your handler using $application->simpleHandler()';
        });
        $this->httpHandler(new WelcomeHandler);
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
    public function httpHandler(HttpHandler $handler) : void
    {
        $this->httpHandler = $handler;
    }

    /**
     * Run the application.
     */
    public function run() : void
    {
        $this->ensureTempDirectoryExists();

        $event = $this->readLambdaEvent();

        // Run the appropriate handler
        if (isset($event['httpMethod'])) {
            // HTTP request
            $response = $this->httpHandler->handle($event);
            $output = $response->toJson();
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
}

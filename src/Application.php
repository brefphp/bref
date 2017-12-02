<?php
declare(strict_types=1);

namespace PhpLambda;

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
     * Run a simple handler.
     */
    public function run(callable $handler) : void
    {
        $this->ensureTempDirectoryExists();

        $event = $this->readLambdaEvent();

        $output = $handler($event);

        $this->writeLambdaOutput(json_encode($output));
    }

    /**
     * Run an HTTP application.
     */
    public function http(HttpApplication $httpApplication) : void
    {
        $this->run(function (array $event) use ($httpApplication) : string {
            if (isset($event['httpMethod'])) {
                $response = $httpApplication->process($event);
            } else {
                $response = new LambdaResponse(
                    400,
                    [
                        'Content-Type' => 'application/json',
                    ],
                    json_encode('This application must be called through HTTP')
                );
            }

            return $response->toJson();
        });
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

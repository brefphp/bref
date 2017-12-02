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
     * Run an HTTP application.
     */
    public function http(HttpApplication $httpApplication) : void
    {
        $filesystem = new Filesystem;
        if (! $filesystem->exists(self::LAMBDA_DIRECTORY)) {
            $filesystem->mkdir(self::LAMBDA_DIRECTORY);
        }

        $event = [];
        if ($filesystem->exists(self::INPUT_FILE_NAME)) {
            $event = (array) json_decode(file_get_contents(self::INPUT_FILE_NAME), true);
        }

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

        file_put_contents(self::OUTPUT_FILE_NAME, $response->toJson());
    }
}

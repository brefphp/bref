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

    public function run(callable $handler)
    {
        $filesystem = new Filesystem;
        if (! $filesystem->exists(self::LAMBDA_DIRECTORY)) {
            $filesystem->mkdir(self::LAMBDA_DIRECTORY);
        }

        $event = [];
        if ($filesystem->exists(self::INPUT_FILE_NAME)) {
            $event = json_decode(file_get_contents(self::INPUT_FILE_NAME), true);
        }

        $result = $handler($event);

        // This is the format required by the AWS_PROXY lambda integration
        // See https://stackoverflow.com/questions/43708017/aws-lambda-api-gateway-error-malformed-lambda-proxy-response
        $lambdaResponse = [
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-phplambda' => 'phplambda',
            ],
            'body' => json_encode($result),
        ];

        file_put_contents(self::OUTPUT_FILE_NAME, json_encode($lambdaResponse));
    }
}

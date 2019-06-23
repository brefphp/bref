<?php declare(strict_types=1);

namespace Bref\Lambda;

use Aws\Lambda\LambdaClient;
use Psr\Http\Message\StreamInterface;

/**
 * A simpler alternative to the official LambdaClient from the AWS SDK.
 */
final class SimpleLambdaClient
{
    /** @var LambdaClient */
    private $lambda;

    public function __construct(string $region)
    {
        $args = [
            'version' => 'latest',
            'region' => $region,
        ];

        if ($awsKey = getenv('AWS_KEY') && $awsSecret =getenv('AWS_SECRET')) {
            $args['credentials'] = new Credentials($awsKey, $awsSecret);
        }

        $this->lambda = new LambdaClient($args);
    }

    /**
     * Synchronously invoke a function.
     *
     * @param mixed $event Event data (can be null).
     * @throws InvocationFailed
     */
    public function invoke(string $functionName, $event = null): InvocationResult
    {
        $rawResult = $this->lambda->invoke([
            'FunctionName' => $functionName,
            'LogType' => 'Tail',
            'Payload' => $event ?? '',
        ]);

        /** @var StreamInterface $resultPayload */
        $resultPayload = $rawResult->get('Payload');
        $resultPayload = json_decode($resultPayload->getContents(), true);

        $invocationResult = new InvocationResult($rawResult, $resultPayload);

        $error = $rawResult->get('FunctionError');
        if ($error) {
            throw new InvocationFailed($invocationResult);
        }

        return $invocationResult;
    }
}

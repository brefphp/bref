<?php

declare(strict_types=1);

namespace Bref\Http;

use Psr\Http\Message\ResponseInterface;

class LambdaResponseArraySerializer
{
    /**
     * @param ResponseInterface $response
     * @return array
     */
    public function __invoke(ResponseInterface $response): array
    {
        $headers = $response->getHeaders();

        // The headers must be a JSON object. If the PHP array is empty it is
        // serialized to `[]` (we want `{}`) so we force it to an empty object.
        if (empty($headers)) {
            $multiValueHeaders = new \stdClass();
        } else {
            $multiValueHeaders = [];

            foreach ($headers as $name => $values) {
                $multiValueHeaders[$this->filterHeader($name)] = $values;
            }
        }

        // This is the format required by the AWS_PROXY lambda integration
        // @see https://docs.aws.amazon.com/apigateway/latest/developerguide/set-up-lambda-proxy-integrations.html#api-gateway-simple-proxy-for-lambda-output-format
        return [
            'isBase64Encoded' => false,
            'statusCode' => $response->getStatusCode(),
            'multiValueHeaders' => $multiValueHeaders,
            'body' => (string)$response->getBody(),
        ];
    }

    /**
     * Filter a header name to wordcase
     *
     * @see https://github.com/zendframework/zend-diactoros/blob/754a2ceb7ab753aafe6e3a70a1fb0370bde8995c/src/Response/SapiEmitterTrait.php#L96
     * @param string $header
     * @return string
     */
    private function filterHeader($header): string
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);
        return str_replace(' ', '-', $filtered);
    }
}

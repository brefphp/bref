<?php
declare(strict_types=1);

namespace Bref\Http;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\HtmlResponse;

/**
 * Formats the response expected by AWS Lambda and the API Gateway integration.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class LambdaResponse
{
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;

    /**
     * @var LambdaResponseArraySerializer
     */
    private $serializer;

    /**
     * LambdaResponse constructor.
     * @param ResponseInterface $response
     * @param LambdaResponseArraySerializer|null $serializer
     */
    public function __construct(ResponseInterface $response, LambdaResponseArraySerializer $serializer = null)
    {
        $this->response = $response;
        $this->serializer = $serializer ?: new LambdaResponseArraySerializer(false);
    }

    public static function fromPsr7Response(ResponseInterface $response): self
    {
        return new self($response);
    }

    public static function fromHtml(string $html): self
    {
        return new self(new HtmlResponse($html));
    }

    public function toJson(): string
    {
        return json_encode($this->serializer->__invoke($this->response));
    }
}

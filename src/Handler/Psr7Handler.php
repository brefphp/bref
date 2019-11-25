<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Http\LambdaResponse;
use Bref\Http\RequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Callable for Bref that allows using PSR-7 request/responses
 *
 * The request handler must be a callable that takes a
 * ServerRequestInterface and emits a ResponseInterface, or alternatively
 * a RequestHandlerInterface.
 *
 * @see RequestHandlerInterface
 * @see ServerRequestInterface
 * @see ResponseInterface
 */
final class Psr7Handler
{
    /** @var callable */
    private $handler;
    /** @var RequestCreator */
    private $requestCreator;

    /**
     * @param callable|RequestHandlerInterface $handler
     */
    public function __construct($handler, ?RequestCreator $requestCreator = null)
    {
        $this->handler = $handler;
        if ($requestCreator === null) {
            $requestCreator = new RequestCreator;
        }
        $this->requestCreator = $requestCreator;
    }

    /**
     * @param array $event
     * @param mixed $context
     * @return array
     */
    public function __invoke(array $event, $context): array
    {
        $request = $this->requestCreator->createRequest($event, $context);

        $handler = $this->handler;
        if ($handler instanceof RequestHandlerInterface) {
            $response = $handler->handle($request);
        } else {
            $response = ($this->handler)($request);
        }

        return LambdaResponse::fromPsr7Response($response)->toApiGatewayFormat();
    }
}

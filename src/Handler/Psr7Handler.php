<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Http\LambdaResponse;
use Bref\Http\RequestCreator;

/**
 * Class Psr7Handler
 */
final class Psr7Handler
{
    /** @var callable */
    private $handler;
    /** @var RequestCreator */
    private $requestCreator;

    public function __construct(callable $handler, ?RequestCreator $requestCreator = null)
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

        $response = ($this->handler)($request);

        return LambdaResponse::fromPsr7Response($response)->toApiGatewayFormat();
    }
}

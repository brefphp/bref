<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use Psr\Http\Server\RequestHandlerInterface;

class Psr15Handler extends HttpHandler
{
    /** @var RequestHandlerInterface */
    private $psr15Handler;

    public function __construct(RequestHandlerInterface $psr15Handler)
    {
        $this->psr15Handler = $psr15Handler;
    }

    public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
    {
        $request = Psr7Bridge::convertRequest($event, $context);

        $response = $this->psr15Handler->handle($request);

        return Psr7Bridge::convertResponse($response);
    }
}

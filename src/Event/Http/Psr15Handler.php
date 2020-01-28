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
        $request = Psr7RequestFactory::fromEvent($event);
        $request = $request->withAttribute('lambda-event', $event);
        $request = $request->withAttribute('lambda-context', $context);

        $response = $this->psr15Handler->handle($request);

        return HttpResponse::fromPsr7Response($response);
    }
}

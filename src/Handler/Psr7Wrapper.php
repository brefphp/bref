<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Context\Context;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\Psr7RequestFactory;
use Bref\Http\HttpResponse;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A handler to add support to RequestHandlerInterface.
 */
class Psr7Wrapper implements BrefHandler
{
    /** @var RequestHandlerInterface */
    private $callable;

    public function __construct(RequestHandlerInterface $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($event, Context $context)
    {
        $request = Psr7RequestFactory::fromEvent(new HttpRequestEvent($event));
        $response = $this->callable->handle($request);

        return HttpResponse::fromPsr7Response($response);
    }
}

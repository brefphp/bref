<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use Bref\Event\Handler;

abstract class HttpHandler implements Handler
{
    abstract public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse;

    /** {@inheritDoc} */
    public function handle($event, Context $context): array
    {
        // See https://bref.sh/docs/runtimes/http.html#cold-starts
        if (isset($event['warmer']) && $event['warmer'] === true) {
            // Delay the response to ensure concurrent invocation
            // See https://github.com/brefphp/bref/pull/734
            usleep(10000); // 10ms
            return ['Lambda is warm'];
        }

        $httpEvent = new HttpRequestEvent($event);

        $response = $this->handleRequest($httpEvent, $context);

        if ($httpEvent->isFormatV2()) {
            return $response->toApiGatewayFormatV2();
        }

        return $response->toApiGatewayFormat($httpEvent->hasMultiHeader());
    }
}

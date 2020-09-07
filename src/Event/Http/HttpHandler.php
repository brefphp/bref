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
            $delay = getenv('WARMUP_DELAY');
            if (is_numeric($delay)) {
                // Delay the response to ensure concurrent invocation
                usleep($delay * 1000);
            }
            return ['Lambda is warm'];
        }

        $httpEvent = new HttpRequestEvent($event);

        $response = $this->handleRequest($httpEvent, $context);

        return $response->toApiGatewayFormat($httpEvent->hasMultiHeader());
    }
}

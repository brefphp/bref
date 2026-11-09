<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Runtime\StreamedResponse;
use Generator;
use Throwable;

abstract class HttpHandler implements Handler
{
    abstract public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse;

    /** {@inheritDoc} */
    public function handle($event, Context $context): array|StreamedResponse
    {
        // See https://bref.sh/docs/runtimes/http.html#cold-starts
        if (isset($event['warmer']) && $event['warmer'] === true) {
            // Delay the response to ensure concurrent invocation
            // See https://github.com/brefphp/bref/pull/734
            usleep(10000); // 10ms
            return ['Lambda is warm'];
        }

        // Function URL events have the same format as HTTP API v2 events, only their domain differs
        if (str_contains($event['requestContext']['domainName'] ?? '', '.lambda-url.')) {
            return $this->stream($event, $context);
        }

        $httpEvent = new HttpRequestEvent($event);

        $response = $this->handleRequest($httpEvent, $context);

        if ($httpEvent->isFormatV2()) {
            return $response->toApiGatewayFormatV2($context->getAwsRequestId(), $this->acceptsGzip($httpEvent));
        }

        return $response->toApiGatewayFormat($httpEvent->hasMultiHeader(), $context->getAwsRequestId(), $this->acceptsGzip($httpEvent));
    }

    /**
     * Function URLs (in `RESPONSE_STREAM` mode) send the response to the client as it is produced.
     *
     * Lambda no longer turns errors into a 502 response for streamed responses: the 500 is sent here
     * (otherwise the error, stack trace included, would be the body of a 200 response).
     */
    private function stream(array $rawEvent, Context $context): StreamedResponse
    {
        $chunks = function () use ($rawEvent, $context): Generator {
            try {
                $event = new HttpRequestEvent($rawEvent);
                $response = $this->handleRequest($event, $context);
            } catch (Throwable $e) {
                yield from (new HttpResponse('Internal Server Error', ['content-type' => 'text/plain'], 500))->toStreamedResponse()->chunks;

                throw $e;
            }
            yield from $response->toStreamedResponse($context->getAwsRequestId(), $this->acceptsGzip($event))->chunks;
        };

        return new StreamedResponse('application/vnd.awslambda.http-integration-response', $chunks());
    }

    private function acceptsGzip(HttpRequestEvent $event): bool
    {
        foreach (explode(',', implode(',', $event->getHeaders()['accept-encoding'] ?? [])) as $coding) {
            [$name, $parameters] = explode(';', $coding, 2) + [1 => ''];
            if (in_array(strtolower(trim($name)), ['gzip', '*'], true)) {
                // `gzip;q=0` means "not gzip"
                return ! preg_match('/q\s*=\s*0(\.0*)?\s*$/', $parameters);
            }
        }

        return false;
    }
}

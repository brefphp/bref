<?php


namespace Bref\Event\Http;

use Bref\Context\Context;

/**
 * Handles scenarios where we did not get a proper response from PHP-FPM. Use
 * an instance of this class to create a nicer error response than "internal server error".
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface FpmExceptionHandler
{
    /**
     * @param HttpRequestEvent $event
     * @param Context $context
     * @param \Throwable $exception
     */
    public function getResponse(HttpRequestEvent $event, Context $context, \Throwable $exception): HttpResponse;
}
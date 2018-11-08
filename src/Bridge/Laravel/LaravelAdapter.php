<?php
declare(strict_types=1);

namespace Bref\Bridge\Laravel;

use Illuminate\Contracts\Http\Kernel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

/**
 * Adapter for using the Laravel framework as a HTTP handler.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class LaravelAdapter implements RequestHandlerInterface
{
    /**
     * @var Kernel
     */
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        // Create a Symfony request that will be used by Laravel
        $httpFoundationFactory = new HttpFoundationFactory;
        $symfonyRequest = $httpFoundationFactory->createRequest($request);

        // We create Laravel's HTTP request from the Symfony request
        // We cannot use Symfony's request directly because the Kernel's implementation
        // expects a `Illuminate\Http\Request` implementation.
        $laravelRequest = \Illuminate\Http\Request::createFromBase($symfonyRequest);
        // Laravel does not forward the headers from the Symfony request
        // we need to do that explicitly :'(
        $laravelRequest->headers->replace($symfonyRequest->headers->all());

        /** @var \Illuminate\Http\Response $laravelResponse */
        $laravelResponse = $this->kernel->handle($laravelRequest);
        $this->kernel->terminate($laravelRequest, $laravelResponse);

        $psr7Factory = new DiactorosFactory;
        // The Laravel response extends Symfony so this works fine here
        $response = $psr7Factory->createResponse($laravelResponse);

        return $response;
    }
}

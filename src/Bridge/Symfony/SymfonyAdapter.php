<?php
declare(strict_types=1);

namespace PhpLambda\Bridge\Symfony;

use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * Adapter for using the Symfony framework as a HTTP handler.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class SymfonyAdapter implements RequestHandlerInterface
{
    /**
     * @var HttpKernelInterface
     */
    private $httpKernel;

    public function __construct(HttpKernelInterface $httpKernel)
    {
        $this->httpKernel = $httpKernel;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $httpFoundationFactory = new HttpFoundationFactory;
        $symfonyRequest = $httpFoundationFactory->createRequest($request);

        $symfonyResponse = $this->httpKernel->handle($symfonyRequest);
        if ($this->httpKernel instanceof TerminableInterface) {
            $this->httpKernel->terminate($symfonyRequest, $symfonyResponse);
        }

        $psr7Factory = new DiactorosFactory;
        $response = $psr7Factory->createResponse($symfonyResponse);

        return $response;
    }
}

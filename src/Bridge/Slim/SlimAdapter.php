<?php
declare(strict_types=1);

namespace PhpLambda\Bridge\Slim;

use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

/**
 * Adapter for using the Slim framework as a HTTP handler.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class SlimAdapter implements RequestHandlerInterface
{
    /**
     * @var App
     */
    private $slim;

    public function __construct(App $slim)
    {
        $this->slim = $slim;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $response = $this->slim->getContainer()->get('response');

        /** @var ResponseInterface $response */
        $response = $this->slim->process($request, $response);

        return $response;
    }
}

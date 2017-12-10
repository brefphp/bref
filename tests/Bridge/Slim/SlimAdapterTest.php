<?php
declare(strict_types=1);

namespace PhpLambda\Test\Bridge\Slim;

use PhpLambda\Bridge\Slim\SlimAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Zend\Diactoros\ServerRequest;

class SlimAdapterTest extends TestCase
{
    public function test Slim applications are adapted()
    {
        $slim = new App;
        $slim->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello world!');
            return $response;
        });

        $adapter = new SlimAdapter($slim);
        $response = $adapter->handle(new ServerRequest([], [], '/foo'));

        self::assertEquals('Hello world!', (string) $response->getBody());
    }
}

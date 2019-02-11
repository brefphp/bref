<?php

namespace Bref\Test\Runtime;

use Bref\Runtime\LambdaRuntime;
use Bref\Test\Server;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class LambdaRuntimeTest extends TestCase
{
    protected function setUp()
    {
        Server::start();
    }

    protected function tearDown()
    {
        Server::stop();
    }


    public function testRuntime()
    {
        Server::enqueue([
            new Response( // lambda event
                '200',
                [
                    'lambda-runtime-aws-request-id' => 1
                ],
                '{ "Hello": "world!"}'
            ),
            new Response(200) // lambda response accepted
        ]);

        $r = new LambdaRuntime('localhost:8126');
        $r->processNextEvent(
            function ($event) {
                return ['hello' => 'world'];
            }
        );
        $responses = Server::received();
        /** @var Request $eventRequest */
        $eventRequest = $responses[0];
        /** @var Request $eventResponse */
        $eventResponse = $responses[1];
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $eventResponse->getUri()->__toString());
        $this->assertJsonStringEqualsJsonString('{"hello": "world"}', $eventResponse->getBody()->__toString());
    }
}

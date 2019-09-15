<?php

declare(strict_types=1);

namespace Bref\Test\Http;

use Bref\Http\LambdaRequest;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request as SfRequest;

class LambdaRequestTest extends TestCase
{
    /**
     * This make sure we always pass the raw response
     * @dataProvider rawEventProvider
     */
    public function testGetRawEvent(string $file, array $expected)
    {
        $request = $this->getRequestFromJsonFile($file);

        $this->assertEquals($expected, $request->getRawEvent());
    }

    /**
     * @dataProvider symfonyRequestProvider
     */
    public function testGetSymfonyEvent(string $file, SfRequest $expected)
    {
        $request = $this->getRequestFromJsonFile($file);

        $this->assertEquals($expected, $request->getSymfonyRequest());
    }

    /**
     * @dataProvider psr7RequestProvider
     */
    public function testGetPsr7Event(string $file, RequestInterface $expected)
    {
        $request = $this->getRequestFromJsonFile($file);

        $this->assertEquals($expected, $request->getPsr7Request());
    }


    /**
     * This will automatically find all lambdaRequest*.json files in the fixture folder.
     */
    public function rawEventProvider()
    {
        $dir = dirname(__DIR__) . '/Fixture/Http/';
        foreach (glob($dir.'lambdaRequest*.json') as $path) {
            yield basename($path) => [$path, json_decode(file_get_contents($path), true)];
        }
    }

    public function symfonyRequestProvider()
    {
        $dir = dirname(__DIR__) . '/Fixture/Http/';

        yield 'lambdaRequest0.json' => [
            $dir.'lambdaRequest0.json',
            SfRequest::create('/hello-world', 'GET')
        ];

    }

    public function psr7RequestProvider()
    {
        $dir = dirname(__DIR__) . '/Fixture/Http/';

        yield 'lambdaRequest0.json' => [
            $dir.'lambdaRequest0.json',
            new Psr7Request('GET', '/hello-world')
        ];
    }

    private function getRequestFromJsonFile(string $file): LambdaRequest
    {
        $array = json_decode(file_get_contents($file), true);

        return LambdaRequest::create($array);
    }
}
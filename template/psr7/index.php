<?php declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function lambda_psr7 as lambda;

require __DIR__.'/vendor/autoload.php';

lambda(function (Request $request): Response {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(200);

    $eventName = $request->getAttribute('event')['name'] ?? 'world';
    $response->getBody()->write('Hello ' . $eventName);

    return $response;
});

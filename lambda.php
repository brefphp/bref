<?php
declare(strict_types=1);

use PhpLambda\Bridge\Slim\SlimAdapter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Debug\Debug;

ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__.'/vendor/autoload.php';

Debug::enable();

$slim = new Slim\App;
$slim->get('/dev', function (ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write('Hello world!');
    return $response;
});
$slim->get('/dev/json', function (ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write(json_encode(['hello' => 'json']));
    $response = $response->withHeader('Content-Type', 'application/json');
    return $response;
});

$app = new \PhpLambda\Application;
$app->simpleHandler(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
$app->httpHandler(new SlimAdapter($slim));
$app->run();

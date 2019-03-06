<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

require __DIR__ . '/../vendor/autoload.php';

/** @noinspection PhpComposerExtensionStubsInspection */
$pdo = new \PDO('mysql:host=example.com;dbname=db');
$pdo->exec('SELECT * FROM foo');

$slim = new Slim\App;

$slim->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write('Hello world!');
    return $response;
});
$slim->get('/json', function (ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write(json_encode(['hello' => 'json']));
    $response = $response->withHeader('Content-Type', 'application/json');
    return $response;
});

$slim->run();

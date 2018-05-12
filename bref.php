<?php
declare(strict_types=1);

use Bref\Bridge\Slim\SlimAdapter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Silly\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;

require __DIR__.'/vendor/autoload.php';

Debug::enable();

// HTTP application using Slim
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

// CLI application using Silly
$silly = new Application;
$silly->command('hello [name]', function (string $name = 'World!', OutputInterface $output) {
    $output->writeln('Hello ' . $name);
});

$app = new \Bref\Application;
$app->simpleHandler(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
$app->httpHandler(new SlimAdapter($slim));
$app->cliHandler($silly);
$app->run();

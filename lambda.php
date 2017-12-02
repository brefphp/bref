<?php
declare(strict_types=1);

use PhpLambda\Bridge\Slim\RequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Debug\Debug;

ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__.'/vendor/autoload.php';

Debug::enable();

$app = new \PhpLambda\Application();

$app->run(function (array $event) {
    $app = new Slim\App;

    $app->get('/dev', function (ServerRequestInterface $request, ResponseInterface $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });
    $app->get('/dev/json', function (ServerRequestInterface $request, ResponseInterface $response) {
        $response->getBody()->write(json_encode(['hello' => 'json']));
        $response = $response->withHeader('Content-Type', 'application/json');
        return $response;
    });

    $request = (new RequestFactory)->createRequest($event);
    $response = $app->getContainer()->get('response');
    /** @var ResponseInterface $response */
    $response = $app->process($request, $response);

    // The lambda proxy integration does not support arrays in headers
    $responseHeader = array_map(function ($header) {
        if (is_array($header)) {
            return $header[0];
        }
        return $header;
    }, $response->getHeaders());

    // This is the format required by the AWS_PROXY lambda integration
    // See https://stackoverflow.com/questions/43708017/aws-lambda-api-gateway-error-malformed-lambda-proxy-response
    $response->getBody()->rewind();
    return [
        'isBase64Encoded' => false,
        'statusCode' => $response->getStatusCode(),
        'headers' => $responseHeader,
        'body' => $response->getBody()->getContents(),
    ];
});

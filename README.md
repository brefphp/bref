# Framework for using AWS Lambda in PHP

Use cases:

- APIs
- GitHub webhooks
- Slack bots
- web tasks
- crons
- workers

## TODO

- Symfony integration
- Silly/Symfony Console integration
- Allow configuring the file name of the application (`lambda.php`)
- CLI helper to run a CLI command on lambda
- Test framework

## Setup

- Create an AWS account if you don't already have one
- Install [serverless](https://serverless.com): `npm install -g serverless`
- Setup your AWS credentials: [create an AWS access key](https://serverless.com/framework/docs/providers/aws/guide/credentials#creating-aws-access-keys) and either configure it [using an environment variable](https://serverless.com/framework/docs/providers/aws/guide/credentials#quick-setup) or [setup `aws-cli`](http://docs.aws.amazon.com/cli/latest/userguide/installing.html) (and run `aws configure`)

PHPLambda will then use AWS credentials and the serverless framework to deploy your application.

## Creating a lambda

```shell
$ composer require phplambda/phplambda
$ vendor/bin/phplambda init
```

Write a `lambda.php` file at the root of your project:

```php
<?php

require __DIR__.'/vendor/autoload.php';

$app = new \PhpLambda\Application;

$app->run(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
```

Watch out: the `run()` method only works for lambdas invoked manually (`serverless invoke`). If you want to use HTTP (e.g. the webhook) you need to use an HTTP framework. This is described at the end of this page.

## Deployment

```shell
$ serverless deploy
```

## Invocation

By default lambdas are deployed with a webhook. You can trigger them by simply calling the webhook. If in doubt, the webhook can be retrieved using `serverless info`.

```shell
$ curl https://xxxxx.execute-api.xxxxx.amazonaws.com/dev/
```

Triggering a lambda manually:

```shell
# "main" is the name of the function created by default
# you can have several functions in the same projects
$ serverless invoke -f main
```

Triggering a lambda from another PHP application:

```php
$lambda = \AWS::createClient('lambda');
$lambda->invoke([
    'FunctionName' => '<function-name>',
    'InvocationType' => 'Event',
    'LogType' => 'None',
    'Payload' => json_encode([
        ...
    ]),
]);
```

## Deletion

```shell
$ serverless remove
```

## HTTP applications

PHPLambda provides bridges to use your HTTP framework and write an HTTP application. Here is an example using the [Slim](https://www.slimframework.com) framework to handle requests:

```php
<?php

require __DIR__.'/vendor/autoload.php';

$slim = new Slim\App;
$slim->get('/dev', function (ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write('Hello world!');
    return $response;
});

$app = new \PhpLambda\Application;
$app->http(new SlimAdapter($slim));
```

## Tests

TODO

How to test a lambda?

```php
// Grab the $app

$lambda = \PhpLambda\TestClient($app);
$result = $lambda->invoke($functionName, [
    ...
]);

$this->assertEquals(0, $result->getExitCode());
$this->assertEquals('Foo bar', $result->getOutput());
```

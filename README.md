# Framework for using AWS Lambda in PHP

Use cases:

- GitHub webhooks
- Slack bots
- web tasks

## Creating a lambda

```shell
$ composer require phplambda/phplambda
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

Add a `.lambda.yml` at the root of your project:

```yaml
name: <function-name>
s3:
    bucket: <bucket-name>
```

How to deploy a lambda?

```shell
$ phplambda deploy <function-name>
```

How to list deployed PHP lambdas?

```shell
$ phplambda list
mylambda1
mylambda2
```

How to run a lambda from CLI?

```shell
$ aws lambda invoke --function-name <function-name> --log-type Tail --payload file://input.json output.json
# or
$ phplambda invoke <function-name> <input.json>
```

How to run a lambda from PHP?

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

// or

$lambda = \PhpLambda\Client();
$lambda->invoke($functionName, [
    ...
]);
```

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

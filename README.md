# Framework for using AWS Lambda in PHP

Use cases:

- GitHub webhooks
- Slack bots
- web tasks

## TODO

- Auto-creating the S3 bucket
- Auto-creating the IAM role
- Delete the function
- Allow configuring the file name of the application (`lambda.php`)
- Cache binaries in a temp directory
- Test framework

## Setup

You must [install the AWS command line interface](http://docs.aws.amazon.com/cli/latest/userguide/installing.html) and configure it:

- create a *User* in IAM with the policy *AdministratorAccess* (or more precise if you know what you are doing)
- configure AWS cli with the public and secret key of that user: `aws configure`

Once AWS is setup the phplambda program will use the same credentials than AWS cli to connect to AWS. Everything *should* work out of the box.

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
    region: eu-west-1
    bucket: <bucket-name>
```

How to deploy a lambda?

```shell
$ phplambda deploy
The 'foo' lambda does not exist, creating...
The lambda function can now be triggered using https://xxxxx.execute-api.xxxxx.amazonaws.com/prod/trigger
```

How to list deployed PHP lambdas?

```shell
$ phplambda list
mylambda1
mylambda2
```

How to run the lambda from a webhook? Simply open the URL show after the creation of the lambda.

How to run a lambda from CLI?

```shell
$ aws lambda invoke --function-name <function-name> --log-type Tail --payload file://input.json output.json
# or
$ phplambda invoke <input.json>
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

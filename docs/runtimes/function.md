---
title: PHP functions
currentMenu: php-functions
introduction: Learn how to run serverless PHP functions on AWS Lambda using Bref.
previous:
    link: /docs/runtimes/
    title: What are runtimes?
next:
    link: /docs/runtimes/http.html
    title: HTTP applications
---

The simplest way to write a lambda is to write one in the form of a PHP function:

```php
<?php

require __DIR__.'/vendor/autoload.php';

lambda(function (array $event) {
    return 'Hello ' . ($event['name'] ?? 'world');
});
```

This form is very similar to lambdas written in other languages, [for example JavaScript](https://docs.aws.amazon.com/lambda/latest/dg/nodejs-prog-model-handler.html):

```javascript
exports.myHandler = async function (event, context) {
   return "Hello " + event.name;
}
```

## The function

A function can be defined by calling Bref's `lambda()` function and passing it a *callable*. The callable can be an anonymous function or [any kind of callable supported by PHP](http://php.net/manual/en/language.types.callable.php).

```php
<?php

require __DIR__.'/vendor/autoload.php';

lambda(function (array $event) {
    return /* response */;
});
```

The function:

- takes an `$event` parameter which contains data from the event that triggered the function ([list of examples here](https://docs.aws.amazon.com/lambda/latest/dg/eventsources.html))
- can optionally return a response: the response must be serializable to JSON

There must be only one function defined per PHP file.

## SAM configuration

Below is a minimal `template.yaml` to deploy a function. To create it automatically run `vendor/bin/bref init`.

```yaml
AWSTemplateFormatVersion: '2010-09-09'
Transform: 'AWS::Serverless-2016-10-31'
Resources:
    MyFunction:
        Type: AWS::Serverless::Function
        Properties:
            FunctionName: 'my-function'
            CodeUri: .
            Handler: index.php # the name of your PHP file
            Runtime: provided
            Layers:
                - 'arn:aws:lambda:<region>:416566615250:layer:php-72:<version>'
```

The runtime to use is `php`. To learn more check out [the runtimes documentation](/docs/runtimes/README.md).

## Invocation

A PHP function must be invoked via the AWS Lambda API. If you instead want to write a classic HTTP application read the [HTTP guide](http.md).

### CLI

A PHP function can be triggered manually from the CLI using the `bref invoke` command:

```bash
$ vendor/bin/bref invoke <function-name>
START RequestId: 3afca641-112c-11e9-bd8b-7108ee29dd18 Version: $LATEST
END RequestId: 3afca641-112c-11e9-bd8b-7108ee29dd18
REPORT RequestId: c42dce1e-112c-11e9-bed8-3f4bd1a349e2	Duration: 201.14 ms	Billed Duration: 300 ms 	Memory Size: 128 MB	Max Memory Used: 39 MB

"Hello world"
```

The logs will be displayed above the function's result encoded as JSON.

To pass event data to the lambda use the `--event` option. For example:

```bash
vendor/bin/bref invoke <function-name> --event='{"name": "John" }'
```

Run `vendor/bin/bref invoke --help` to learn more about the `invoke` command.

### From PHP applications

A PHP function can be triggered from another PHP application using the AWS PHP SDK:

```php
$lambda = new \Aws\Lambda\LambdaClient([
    'version' => 'latest',
    'region' => <region>,
]);

$result = $lambda->invoke([
    'FunctionName' => '<function-name>',
    'InvocationType' => 'RequestResponse',
    'LogType' => 'None',
    'Payload' => json_encode(/* event data */),
]);

$result = json_decode($result->get('Payload')->getContents(), true);
```

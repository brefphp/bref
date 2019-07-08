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

### Context

The function is invoked with the `$event` parameter as well a `$context` parameter that can be optionally declared if you want to use it:

```php
<?php

use Bref\Context\Context;

require __DIR__.'/vendor/autoload.php';

lambda(function (array $event, Context $context) {
    return /* response */;
});
```

The `Context` object is inspired from the [`context` parameter in other languages](https://docs.aws.amazon.com/lambda/latest/dg/nodejs-prog-model-context.html) and provides information about the current lambda invocation (the request ID, the X-Ray trace ID, etc.).

## `serverless.yml` configuration

Below is a minimal `serverless.yml` to deploy a function. To create it automatically run `vendor/bin/bref init`.

```yaml
service: app
provider:
    name: aws
    runtime: provided
plugins:
    - ./vendor/bref/bref
functions:
    hello:
        handler: index.php
        layers:
            - ${bref:layer.php-73}
```

The runtime to use is `php`. To learn more check out [the runtimes documentation](/docs/runtimes/README.md).

## Invocation

A PHP function must be invoked via the AWS Lambda API. If you instead want to write a classic HTTP application read the [HTTP guide](http.md).

### CLI

A PHP function can be triggered manually from the CLI using the [`serverless invoke` command](https://serverless.com/framework/docs/providers/aws/cli-reference/invoke/):

```bash
$ serverless invoke -f <function-name>
"Hello world"
```

To pass event data to the lambda use the `--data` option. For example:

```bash
serverless invoke -f <function-name> --data='{"name": "John" }'
```

Run `serverless invoke --help` to learn more about the `invoke` command.

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

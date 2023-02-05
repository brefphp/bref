---
title: PHP Lambda functions
current_menu: php-functions
introduction: Run serverless event-driven PHP functions on AWS Lambda using Bref.
next:
    link: /docs/function/handlers.html
    title: Typed PHP Lambda handlers
---

Previously, we saw how to use AWS Lambda as web hosting for complete web applications.
But we can also run event-driven **PHP functions** on AWS Lambda.

Here is an example of a PHP Lambda function written as an anonymous function:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

return function ($event) {
    return 'Hello ' . ($event['name'] ?? 'world');
};
```

This form is very similar to lambdas written in other languages, [for example JavaScript](https://docs.aws.amazon.com/lambda/latest/dg/nodejs-prog-model-handler.html):

```javascript
exports.myHandler = async function (event, context) {
   return "Hello " + event.name;
}
```

Writing functions is very useful to process events and data from other AWS services.
For example, this is perfect to implement **asynchronous workers, event handling, file processing**, etc.

If you are looking to create HTTP applications, have a look at [Bref for web apps](/docs/runtimes/http.md).

## The function

Functions that can run on Lambda can be an anonymous function or [any kind of callable supported by PHP](http://php.net/manual/en/language.types.callable.php).

```php
<?php

require __DIR__ . '/vendor/autoload.php';

return function ($event) {
    return /* result */;
};
```

The function:

- takes an `$event` parameter which contains data from the event that triggered the function ([list of examples here](https://docs.aws.amazon.com/lambda/latest/dg/eventsources.html))
- can optionally return a result: the result must be serializable to JSON

There can only be one function returned per PHP file.

### Context

The function is invoked with the `$event` parameter as well as a `$context` parameter. This parameter can be optionally declared if you want to use it:

```php
<?php

use Bref\Context\Context;

require __DIR__ . '/vendor/autoload.php';

return function ($event, Context $context) {
    return /* result */;
};
```

The `Context` object is inspired from the [`context` parameter in other languages](https://docs.aws.amazon.com/lambda/latest/dg/nodejs-prog-model-context.html) and provides information about the current lambda invocation (the request ID, the X-Ray trace ID, etc.).

## Deployment configuration

Below is a minimal `serverless.yml` to deploy a function. To create it automatically, run `vendor/bin/bref init`.

```yaml
service: app
provider:
    name: aws
    runtime: provided.al2
plugins:
    - ./vendor/bref/bref
functions:
    hello:
        handler: my-function.php
        runtime: php-81
```

The runtime to use is `php-XX`. To learn more check out [the runtimes documentation](/docs/runtimes/README.md).

## Invocation

A PHP function must be invoked via the AWS Lambda API, either manually or by integrating with other AWS services.

> If you instead want to write a classic **HTTP application** read [Bref for web apps](/docs/runtimes/http.md).

### CLI

A PHP function can be triggered manually from the CLI using the [`serverless invoke` command](https://serverless.com/framework/docs/providers/aws/cli-reference/invoke/):

```bash
$ serverless invoke -f <function-name>
# The function name is the one in serverless.yml, in our example that would be `hello`:
$ serverless invoke -f hello
"Hello world"
```

To pass event data to the lambda use the `--data` option. For example:

```bash
serverless invoke -f <function-name> --data='{"name": "John" }'
```

Run `serverless invoke --help` to learn more about the `invoke` command.

### From PHP applications

A PHP function can be triggered from another PHP application using the AWS PHP SDK:

You first need to install the AWS PHP SDK by running

```bash
$ composer require aws/aws-sdk-php
```

```php
$lambda = new \Aws\Lambda\LambdaClient([
    'version' => 'latest',
    'region' => '<region>',
]);

$result = $lambda->invoke([
    'FunctionName' => '<function-name>',
    'InvocationType' => 'RequestResponse',
    'LogType' => 'None',
    'Payload' => json_encode(/* event data */),
]);

$result = json_decode($result->get('Payload')->getContents(), true);
```

> A lighter alternative to the official AWS PHP SDK is the [AsyncAws Lambda](https://async-aws.com/clients/lambda.html) package.

### From other AWS services

Functions are perfect to react to events emitted by other AWS services.

For example, you can write code that processes new SQS events, SNS messages, new uploaded files on S3, DynamoDb insert and update events, etc.

This can be achieve by configuring which events will trigger your function via `serverless.yml`. Learn more about this [in the Serverless documentation](https://serverless.com/framework/docs/providers/aws/events/).

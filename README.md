Bref helps you build serverless PHP applications.

[![Build Status](https://travis-ci.com/mnapoli/bref.svg?branch=master)](https://travis-ci.com/mnapoli/bref)
[![Latest Version](https://img.shields.io/github/release/mnapoli/bref.svg?style=flat-square)](https://packagist.org/packages/mnapoli/bref)

Bref brings support for PHP on serverless providers (AWS Lambda only for now) but also goes beyond that: it provides a deployment process tailored for PHP as well as the ability to create:

- classic lambdas (a function taking an "event" and returning a result)
- HTTP applications written with popular PHP frameworks
- CLI applications

It is currently in beta version and will get more and more complete with time, but it is used in production successfully. Contributions are welcome!

If you want to understand what serverless is please read the [Serverless and PHP: introducing Bref](http://mnapoli.fr/serverless-php/) article.

Use case examples:

- APIs
- workers
- crons/batch processes
- GitHub webhooks
- Slack bots

Interested about performances? [Head over here](https://github.com/mnapoli/bref-benchmark) for a benchmark.

## Setup

For now Bref only works with AWS Lambda. Help to support other providers is welcome.

For deploying, Bref internally uses [the serverless framework](https://serverless.com/). At first you should not have to deal with the serverless framework itself but you need to install it.

- Create an AWS account if you don't already have one
- Install [the serverless framework](https://serverless.com): `npm install -g serverless`
- Setup your AWS credentials: [create an AWS access key](https://serverless.com/framework/docs/providers/aws/guide/credentials#creating-aws-access-keys) and either configure it [using an environment variable](https://serverless.com/framework/docs/providers/aws/guide/credentials#quick-setup) (easy solution) or [setup `aws-cli`](http://docs.aws.amazon.com/cli/latest/userguide/installing.html) (and run `aws configure`)

Bref will then use AWS credentials and the serverless framework to deploy your application.

## Creating a lambda

To create your first lambda application create an empty directory and run the following commands:

```shell
composer require mnapoli/bref
vendor/bin/bref init
```

The `init` command will create the required files, including a `bref.php` file which will be your application:

```php
<?php

require __DIR__.'/vendor/autoload.php';

λ(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
```

Note that the `λ` function is just a simple function (with a funny name) that boots Bref and runs it, here is the equivalent code without that shortcut function:

```php
$app = new \Bref\Application;
$app->simpleHandler(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
$app->run();
```

For now our lambda is a simple lambda that can be invoked manually. Creating HTTP applications is covered below in the documentation.

Let's deploy our application:

```shell
vendor/bin/bref deploy
```

On the first deploy Bref will create the lambda and every other resource needed. If you redeploy later your existing lambda will be updated.

## Invocation

You can trigger your lambda manually using the CLI:

```shell
vendor/bin/bref invoke
```

Or using the AWS PHP SDK from another PHP application:

```php
$lambda = new \Aws\Lambda\LambdaClient([
    'version' => 'latest',
    'region' => 'us-east-1',
]);
$result = $lambda->invoke([
    'FunctionName' => '<function-name>',
    'InvocationType' => 'Event',
    'LogType' => 'None',
    'Payload' => json_encode([ /* your event data */ ]),
]);
$payload = json_decode($result->get('Payload')->getContents(), true);
```

### Invoking locally

Bref provides a helper to invoke the lambda locally, on your machine instead of the serverless provider:

```shell
php bref.php bref:invoke

# If you want to pass event data:
php bref.php bref:invoke --event='{"name":"foo"}'
```

## HTTP applications

Bref provides bridges to use your HTTP framework to write a HTTP applications. By default Bref supports any [PSR-15](https://github.com/php-fig/http-server-handler) compliant framework, as well as Symfony and Laravel (read below).

Here is an example using the [Slim framework](https://www.slimframework.com) to handle requests (native PSR-15 support for Slim [is in the works](https://github.com/slimphp/Slim/pull/2379)):

```php
<?php

use Bref\Bridge\Slim\SlimAdapter;

require __DIR__.'/vendor/autoload.php';

$slim = new Slim\App;
$slim->get('/dev', function ($request, $response) {
    $response->getBody()->write('Hello world!');
    return $response;
});

$app = new \Bref\Application;
$app->httpHandler(new SlimAdapter($slim));
$app->run();
```

The `$app->httpHandler()` method lets us define the handler for HTTP requests.

When your lambda is deployed, a URL will be created so that your application is accessible online. The URL can be retrieved using `vendor/bin/bref info`. Calling the URL will trigger your lambda and Bref will run your "http handler". For example using curl:

```shell
curl https://xxxxx.execute-api.xxxxx.amazonaws.com/dev/
```

Bref provides a helper to preview the application locally. It works with PHP's built-in webserver:

```shell
php -S 127.0.0.1:8000 bref.php
```

The application is then available at [http://localhost:8000](http://localhost:8000/).

Since Bref works with PSR-15 you are not even required to use a PHP framework. You could write your own "HTTP handler" by providing an implementation of `Psr\Http\Server\RequestHandlerInterface` to `$app->httpHandler()`.

### Symfony integration

Read the documentation for [deploying Symfony applications](docs/Symfony.md).

### Laravel integration

Read the documentation for [deploying Laravel applications](docs/Laravel.md).

### Why is there a `/dev` prefix in the URLs on AWS Lambda

See [this StackOverflow question](https://stackoverflow.com/questions/46857335/how-to-remove-stage-from-urls-for-aws-lambda-functions-serverless-framework) for a more detailed answer. The short version is AWS requires a prefix containing the stage name (dev/prod/…).

If you use a custom domain for your application this prefix will disappear. If you don't, you need to write routes with this prefix in your framework.

## CLI applications

Bref provides an abstraction to easily run CLI commands in lambdas. You can define a CLI application using [Symfony Console](https://symfony.com/doc/master/components/console.html) or [Silly](https://github.com/mnapoli/silly) (which extends and simplifies Symfony Console). Once the lambda is deployed you can then "invoke" the CLI commands *in the lambda* using `bref cli -- <command>`.

```php
<?php

require __DIR__.'/vendor/autoload.php';

$silly = new \Silly\Application;
$silly->command('hello [name]', function (string $name = 'World!', $output) {
    $output->writeln('Hello ' . $name);
});

$app = new \Bref\Application;
$app->cliHandler($silly);
$app->run();
```

To run CLI commands in the lambda, run `bref cli` on your computer:

```shell
$ vendor/bin/bref cli
[…]
# Runs the CLI application without arguments and displays the help

$ vendor/bin/bref cli -- hello
Hello World!

$ vendor/bin/bref cli -- hello Bob
Hello Bob
```

As you can see, all arguments and options after `bref cli --` are forwarded to the CLI command running on lambda.

To test your CLI commands locally (on your machine), run:

```shell
php bref.php <commands and options>
```

Bref automatically registers a special `bref:invoke` command to your CLI application. That command lets you invoke on your machine a `simpleHandler` you may have defined:

```shell
php bref.php bref:invoke
```

## Multiple handlers

As you may have noted, Bref lets you define 3 kinds of handlers:

```php
$app->simpleHandler(function () {
    return 'Hello';
});
$app->httpHandler($httpFramework);
$app->cliHandler($console);

$app->run();
```

If you want to, you can define those 3 handlers in the same application. On execution Bref recognizes if the application is invoked through HTTP, through `bref cli` (for CLI commands) or simply through a standard invocation. It will then execute the appropriate handler.

## Logging

### Writing logs

The filesystem on lambdas is read-only (except for the `/tmp` folder). You should not try to write application logs to disk.

The easiest solution is to push logs to AWS Cloudwatch (Amazon's solution for logs). Bref (and AWS Lambda) will send to Cloudwatch anything you write on `stdout` (using `echo` for example) or `stderr`. If you are using Monolog this means you will need to configure Monolog to write to the output (contribution welcome: clarify with an example).

If you have more specific needs you can of course push logs to anything, for example Logstash, Papertrail, Loggly, etc.

### Reading logs

You can read the AWS Cloudwatch logs in the AWS console or via the CLI:

```shell
vendor/bin/bref logs
```

## Deployment

To deploy the application, run:

```shell
vendor/bin/bref deploy
```

A stage can be provided to deploy to multiple stages, for example staging, production, etc:

```shell
vendor/bin/bref deploy --stage=prod
```

### Build hooks

When deploying Composer dependencies will be installed and optimized for production (`composer install --no-dev --no-scripts --classmap-authoritative`).

You can execute additional scripts by using a *build hook*. Those can be defined in a `.bref.yml` file:

```yaml
hooks:
    build:
        - 'npm install'
```

### PHP configuration

If you need a specific PHP version, you can define it in a `.bref.yml` file:

```yaml
php:
    version: 7.2.5
``` 

Here is the list of versions available:

- 7.2.5

You can also define `php.ini` configuration flags ([full list here](http://php.net/manual/en/ini.list.php)) and activate extensions:

```yaml
php:
    configuration:
        memory_limit: 256M
    extensions:
        - redis
```

Here is the list of extensions available:

- Intl: `intl` (be aware that this extension [adds about 25mb to your lambda](https://docs.aws.amazon.com/lambda/latest/dg/limits.html))
- MongoDB: `mongodb`
- New Relic: `newrelic`
- Redis: `redis`

## Deletion

You can delete your lambda on the hosting provider by running:

```shell
vendor/bin/bref remove
```

## Contributing

There are a lot of detailed `TODO` notes in the codebase. Feel free to work on these.

## Projects using Bref

Here are projects using Bref, feel free to add yours in a pull request:

- [prettyci.com](https://prettyci.com/)
- [returntrue.win](https://returntrue.win/)

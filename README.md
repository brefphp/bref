Bref is a serverless application framework for PHP.

[![Build Status](https://travis-ci.com/mnapoli/bref.svg?branch=master)](https://travis-ci.com/mnapoli/bref)
[![Latest Version](https://img.shields.io/github/release/mnapoli/bref.svg?style=flat-square)](https://packagist.org/packages/mnapoli/bref)

It allows to deploy PHP applications on serverless hosting providers (AWS Lambda mostly for now) and provides everything necessary for it to work, including bridges with popular PHP frameworks.

It is currently in beta version, there are a lot of things missing and it is mainly intended for testing (although it is used in production sucessfully). Contributions are welcome!

If you want to understand Serverless in more depth please read the [Serverless and PHP: introducing Bref](http://mnapoli.fr/serverless-php/) article.

Example of use cases:

- APIs
- GitHub webhooks
- Slack bots
- crons
- workers

Interested about performances? [Head over here](https://github.com/mnapoli/bref-benchmark) for a benchmark.

## Setup

Bref internally uses [the serverless framework](https://serverless.com/) for deployment in order to avoid reinventing the wheel. As a Bref user you should not have to deal with the serverless framework itself but you need to install it.

- Create an AWS account if you don't already have one
- Install [the serverless framework](https://serverless.com): `npm install -g serverless`
- Setup your AWS credentials: [create an AWS access key](https://serverless.com/framework/docs/providers/aws/guide/credentials#creating-aws-access-keys) and either configure it [using an environment variable](https://serverless.com/framework/docs/providers/aws/guide/credentials#quick-setup) (easy solution) or [setup `aws-cli`](http://docs.aws.amazon.com/cli/latest/userguide/installing.html) (and run `aws configure`)

Bref will then use AWS credentials and the serverless framework to deploy your application.

## Creating a lambda

```shell
$ composer require mnapoli/bref
$ vendor/bin/bref init
```

Write a `bref.php` file at the root of your project:

```php
<?php

require __DIR__.'/vendor/autoload.php';

$app = new \Bref\Application;

$app->simpleHandler(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
$app->run();
```

If you want to keep things simple for now, simply use the `λ` shortcut :)

```php
<?php

require __DIR__.'/vendor/autoload.php';

λ(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
```

Watch out: if you want to setup a HTTP handler (e.g. for the webhook) you need to use a HTTP framework. This is described at the end of this page.

## Deployment

```shell
$ vendor/bin/bref deploy
```

## Invocation

By default lambdas are deployed with a webhook. You can trigger them by simply calling the webhook. If in doubt, the webhook can be retrieved using `vendor/bin/bref info`.

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
$lambda = new \Aws\Lambda\LambdaClient([
    'version' => 'latest',
    'region' => 'us-east-1',
]);
$result = $lambda->invoke([
    'FunctionName' => '<function-name>',
    'InvocationType' => 'Event',
    'LogType' => 'None',
    'Payload' => json_encode([...]),
]);
$payload = json_decode($result->get('Payload')->getContents(), true);
```

## Deletion

```shell
$ serverless remove
```

## HTTP applications

Bref provides bridges to use your HTTP framework and write an HTTP application. By default it supports any [PSR-15 request handler](https://github.com/php-fig/http-server-handler) implementation, thanks to PSR-7 it is easy to integrate most frameworks.

Here is an example using the [Slim](https://www.slimframework.com) framework to handle requests (native PSR-15 support for Slim [is in the works](https://github.com/slimphp/Slim/pull/2379)):

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

Bref provides a helper to preview the application locally, simply run:

```shell
$ php -S 127.0.0.1:8000 bref.php
```

And open [http://localhost:8000](http://localhost:8000/).

Remember that you can also keep the `simpleHandler` so that your lambda handles both HTTP requests and direct invocations.

### Symfony integration

Read the documentation for [deploying Symfony applications](docs/Symfony.md).

### Why is there a `/dev` prefix in the URLs on AWS Lambda

See [this StackOverflow question](https://stackoverflow.com/questions/46857335/how-to-remove-stage-from-urls-for-aws-lambda-functions-serverless-framework) for a more detailed answer. The short version is AWS requires a prefix containing the stage name (dev/prod).

If you use a custom domain for your application this prefix will disappear. If you don't, you need to write routes with this prefix in your framework.

## CLI applications

Bref provides an abstraction to easily run CLI commands in lambdas. You can define a CLI application using [Symfony Console](https://symfony.com/doc/master/components/console.html) or [Silly](https://github.com/mnapoli/silly) (which extends and simplifies Symfony Console). Once the lambda is deployed you can then "invoke" the CLI commands in the lambda using `bref cli -- <command>`.

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

To run CLI commands in the lambda, simply run `bref cli` on your computer:

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

To test your CLI commands locally, simply run:

```shell
$ php bref.php <commands and options>
```

## Build hooks

When deploying Composer dependencies will be installed and optimized for production (`composer install --no-dev --no-scripts --classmap-authoritative`).

You can execute additional scripts by using a *build hook*. Those can be defined in a `.bref.yml` file:

```yaml
hooks:
    build:
        - 'npm install'
```

## Contributing

There are a lot of detailed `TODO` notes in the codebase. Feel free to work on these.

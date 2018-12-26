---
layout: home
---

Bref helps you build serverless PHP applications.

[![Build Status](https://travis-ci.com/mnapoli/bref.svg?branch=master)](https://travis-ci.com/mnapoli/bref)
[![Latest Version](https://img.shields.io/github/release/mnapoli/bref.svg?style=flat-square)](https://packagist.org/packages/mnapoli/bref)
[![PrettyCI Status](https://hc4rcprbe1.execute-api.eu-west-1.amazonaws.com/dev?name=mnapoli/bref)](https://prettyci.com/)
[![Monthly Downloads](https://img.shields.io/packagist/dm/mnapoli/bref.svg)](https://packagist.org/packages/mnapoli/bref/stats)

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

lambda(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
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
# With event data:
vendor/bin/bref invoke --event '{"name":"folks"}'
```

Or using the AWS PHP SDK from another PHP application:

```php
$lambda = new \Aws\Lambda\LambdaClient([
    'version' => 'latest',
    'region' => 'us-east-1',
]);
$result = $lambda->invoke([
    'FunctionName' => '<function-name>',
    'InvocationType' => 'RequestResponse',
    'LogType' => 'None',
    'Payload' => json_encode([ /* your event data */ ]),
]);
$payload = json_decode($result->get('Payload')->getContents(), true);
```

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

If you want to tail the logs:

```shell
vendor/bin/bref logs --tail
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

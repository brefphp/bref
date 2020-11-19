---
title: PHP runtimes for AWS Lambda
current_menu: runtimes-introduction
introduction: Bref provides runtimes to bring support for PHP on AWS Lambda.
previous:
    link: /docs/first-steps.html
    title: First steps
next:
    link: /docs/runtimes/function.html
    title: PHP functions
---

There is no built-in support for PHP on AWS Lambda. Instead we need to use 3rd party runtimes via the system of Lambda *layers*.

Bref provides the runtimes (aka layers) needed to run PHP applications, whether they run via functions, HTTP or CLI.

This page is an introduction to the runtimes. The next sections (e.g. PHP functions, HTTP applications) will show how to use them in your project.

## Bref runtimes

### PHP functions: `php-74` and `php-73`

The simplest way to write a lambda is to write one in the form of a PHP function.

This runtime works great for **non-HTTP applications**.

[Read more about the `php-74` runtime here](/docs/runtimes/function.md).

### HTTP applications: `php-74-fpm` and `php-73-fpm`

This runtime uses PHP-FPM to run **HTTP applications** on AWS Lambda.

This runtime is **the easiest to start with**: it works like traditional PHP hosting and is compatible with Symfony and Laravel.

[Read more about the `php-74-fpm` runtime here](/docs/runtimes/http.md).

### Console: `console`

This runtime lets use run console commands on Lambda.

For example we can run the [Symfony Console](https://symfony.com/doc/master/components/console.html) or [Laravel Artisan](https://laravel.com/docs/5.8/artisan).

[Read more about the `console` runtime here](/docs/runtimes/console.md).

## Usage

To use a runtime, import the corresponding layer in `serverless.yml`:

```yaml
service: app
provider:
    name: aws
    runtime: provided.al2
plugins:
    - ./vendor/bref/bref
functions:
    hello:
        ...
        layers:
            - ${bref:layer.php-74}
```

The `${...}` notation is the [syntax to use variables](https://serverless.com/framework/docs/providers/aws/guide/variables/) in `serverless.yml`. Bref provides a serverless plugin ("`./vendor/bref/bref`") that provides those variables:

- `${bref:layer.php-74}`
- `${bref:layer.php-73}`
- `${bref:layer.php-74-fpm}`
- `${bref:layer.php-73-fpm}`
- `${bref:layer.console}`
- `${bref:layer.php-80}`
- `${bref:layer.php-80-fpm}`

Bref currently provides runtimes for PHP 7.3 and 7.4. It also provides **experimental** runtimes for PHP 8.0.

> `php-74` means PHP 7.4.\*. It is not possible to require a specific "patch" version.

You can read more about this in the next sections.

## Lambda layers in details

> **Notice**: this section is only useful if you want to learn more.
>
> You can skip it for now if you just want to get started with Bref.

Bref runtimes are [AWS Lambda layers](https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html). While Bref provides a Serverless plugin to simplify how to use them, you can use the layers directly.

The name of the layers follow this pattern:

```
arn:aws:lambda:<region>:209497400698:layer:<layer-name>:<layer-version>
```

To use them manually you need to use that full name. For example in `serverless.yml`:

```yaml
service: app
provider:
    name: aws
    runtime: provided.al2
functions:
    hello:
        ...
        layers:
            - 'arn:aws:lambda:us-east-1:209497400698:layer:php-73:7'
```

Or if you are using [SAM's `template.yaml`](https://aws.amazon.com/serverless/sam/):

```yaml
AWSTemplateFormatVersion: '2010-09-09'
Transform: AWS::Serverless-2016-10-31
Resources:
    Hello:
        Type: AWS::Serverless::Function
        Properties:
            ...
            Runtime: provided.al2
            Layers:
                - 'arn:aws:lambda:us-east-1:209497400698:layer:php-73:7'
```

> Remember: the layer ARN contains a region. **You need to use the same region as the rest of your application** else Lambda will not find the layer.

### Layer version (`<layer-version>`)

The latest of runtime versions can be found at [runtimes.bref.sh](https://runtimes.bref.sh/) and is shown below:

<iframe src="https://runtimes.bref.sh/embedded" class="w-full h-96"></iframe>

### Bref ping

Bref layers send a ping to estimate the total number of Lambda invocations powered by Bref. That statistic is useful in two ways:

- to provide new users an idea on how much Bref is used in production
- to communicate to AWS how much Bref is used and push for better PHP integration with AWS Lambda tooling

We consider this to be beneficial both to the Bref project (by getting more users and more consideration from AWS) and for Bref users (more users means a larger community, a stronger and more active project, as well as more features from AWS).

#### What is sent

The data sent in the ping is completely anonymous. It does not contain any identifiable data about anything (the project, users, etc.).

The only data it contains is: "A Bref invocation happened".

Anyone can inspect the code and the data sent by checking the [`Bref\Runtime\LambdaRuntime::ping()` function](https://github.com/brefphp/bref/blob/master/src/Runtime/LambdaRuntime.php#L328).

#### How is it sent

The data is sent via the [statsd](https://github.com/statsd/statsd) protocol, over [UDP](https://en.wikipedia.org/wiki/User_Datagram_Protocol).

Unlike TCP, UDP does not check that the message correctly arrived to the server.
It doesn't even establish a connection. That means that UDP is extremely fast:
the data is sent over the network and the code moves on to the next line.
When actually sending data, the overhead of that ping takes about 150 micro-seconds.

However, this function actually sends data every 100 invocation, because we don't
need to measure *all* invocations. We only need an approximation.
That means that 99% of the time, no data is sent, and the function takes 30 micro-seconds.
If we average all executions, the overhead of that ping is about 31 micro-seconds.
Given that it is much much less than even 1 milli-second, we consider that overhead negligible.

#### Disabling

The ping can be disabled by setting a `BREF_PING_DISABLE` environment variable to `1`.

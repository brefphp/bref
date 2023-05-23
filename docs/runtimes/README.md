---
title: PHP runtimes for AWS Lambda
current_menu: runtimes-introduction
introduction: Bref provides runtimes to bring support for PHP on AWS Lambda.
previous:
    link: /docs/first-steps.html
    title: First steps
next:
    link: /docs/runtimes/http.html
    title: Web apps on AWS Lambda
---

There is no built-in support for PHP on AWS Lambda. Instead, we can use 3rd party runtimes via [AWS Lambda *layers*](https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html).

**Bref provides open-source runtimes to run PHP on Lambda** (distributed as AWS Lambda layers).

## Bref runtimes

Bref provides 2 main runtimes:

- The "FPM" runtime, to create **web applications**.
- The "function" runtime, to create **event-driven functions**.

You can see in the documentation menu how these two runtimes are used for two different kinds of applications.

These runtimes are available as AWS Lambda layers that you can use (explained below). They are also published as Docker images so that you can run your applications locally (more on that later).

### Web apps

Name: `php-82-fpm`, `php-81-fpm`, and `php-80-fpm`.

This runtime uses PHP-FPM to run **web applications** on AWS Lambda.

It's **the easiest to start with**: it works like traditional PHP hosting and is compatible with Symfony and Laravel.

[Get started with the FPM runtime in "Bref for web apps"](/docs/runtimes/http.md).

### Event-driven functions

Name: `php-82`, `php-81`, and `php-80`.

AWS Lambda was initially created to run _functions_ (yes, functions of code) in the cloud.

The Bref "function" runtime lets you create Lambda functions in PHP like with any other language.

This runtime works great to create **event-driven micro-services**.

_Note: if you are getting started, we highly recommend using the FPM runtime instead. It's "PHP as usual" (like on any server), with all the benefits of serverless (simplicity, scaling, etc.)._

[Get started with the Function runtime in "Bref for event-driven functions"](/docs/runtimes/function.md).

### Console

Name: `php-82-console`, `php-81-console`, and `php-80-console`.

This runtime lets you run CLI console commands on Lambda.

For example, we can run the [Symfony Console](https://symfony.com/doc/master/components/console.html) or [Laravel Artisan](https://laravel.com/docs/artisan).

[Read more about the `console` runtime here](/docs/runtimes/console.md).

## Usage

To use a runtime, set it on each function in `serverless.yml`:

```yaml
service: app
provider:
    name: aws
plugins:
    - ./vendor/bref/bref
functions:
    hello:
        # ...
        runtime: php-81
        # or:
        runtime: php-81-fpm
        # or:
        runtime: php-81-console
```

Bref currently provides runtimes for PHP 8.0, 8.1 and 8.2:

- `php-82`
- `php-81`
- `php-80`
- `php-82-fpm`
- `php-81-fpm`
- `php-80-fpm`
- `php-82-console`
- `php-81-console`
- `php-80-console`

> `php-80` means PHP 8.0.\*. It is not possible to require a specific "patch" version. The latest Bref versions always aim to support the latest PHP versions, so upgrade frequently to keep PHP up to date.

### ARM runtimes

It is possible to run AWS Lambda functions on [ARM-based AWS Graviton processors](https://aws.amazon.com/blogs/aws/aws-lambda-functions-powered-by-aws-graviton2-processor-run-your-functions-on-arm-and-get-up-to-34-better-price-performance/). This is usually considered a way to reduce costs and improve performance.

You can deploy to ARM by using the `arm64` architecture:

```diff
functions:
    api:
        handler: public/index.php
        runtime: php-81-fpm
+       architecture: arm64
```

The Bref plugin will detect that change and automatically use the Bref ARM Lambda layers.

### The Bref plugin for serverless.yml

Make sure to always include the Bref plugin in your `serverless.yml` config:

```yaml
plugins:
    - ./vendor/bref/bref
```

This plugin is what makes `runtime: php-81` work (as well as other utilities). It is explained in more details in the section below.

### AWS Lambda layers

The `runtime: php-xxx` runtimes we use in `serverless.yml` are not _real_ AWS Lambda runtimes. Indeed, PHP is not supported natively on AWS Lambda.

What the Bref plugin for `serverless.yml` (the one we include with `./vendor/bref/bref`) does is it automatically turns this:

```yaml
functions:
    hello:
        # ...
        runtime: php-81
```

into this:

```yaml
functions:
    hello:
        # ...
        runtime: provided.al2
        layers:
            - 'arn:aws:lambda:us-east-1:534081306603:layer:php-81:21'
```

☝️ `provided.al2` [is the generic Linux environment for custom runtimes](https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html#runtimes-custom-use), and the `layers` config points to Bref's AWS Lambda layers.

Thanks to the Bref plugin, our `serverless.yml` is simpler. It also automatically adapts to the AWS region in use, and automatically points to the correct layer version. You will learn more about "layers" below in this page.

If you want to reference AWS Lambda layers directly (instead of using the simpler `runtime: php-81` syntax), the Bref plugin also provides simple `serverless.yml` variables. These were the default in Bref v1.x, so you may find this older syntax on tutorials and blog posts:

```yaml
service: app
provider:
    name: aws
    runtime: provided.al2
plugins:
    - ./vendor/bref/bref
functions:
    hello:
        # ...
        layers:
            - ${bref:layer.php-80}
            # or:
            - ${bref:layer.php-80-fpm}
```

The `${...}` notation is the [syntax to use variables](https://serverless.com/framework/docs/providers/aws/guide/variables/) in `serverless.yml`. The Bref plugin provides the following variables:

- `${bref:layer.php-82}`
- `${bref:layer.php-81}`
- `${bref:layer.php-80}`
- `${bref:layer.php-82-fpm}`
- `${bref:layer.php-81-fpm}`
- `${bref:layer.php-80-fpm}`
- `${bref:layer.console}`

Bref ARM layers are the same as the x86 layers, but with the `arm-` prefix in their name, for example `${bref:layer.arm-php-82}`. The only exception is `${bref:layer.console}` (this is the same layer for both x86 and ARM).

> **Note**: to be clear, it is easier and recommended to use the `runtime: php-xxx` option instead of setting `layers` directly.

## Lambda layers in details

> **Notice**: this section is only useful if you want to learn more.
>
> You can skip it for now if you just want to get started with Bref.
>
> ▶ [**Get started with web apps**](/docs/runtimes/http.md).

Bref runtimes are distributed as [AWS Lambda layers](https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html). While Bref provides a Serverless plugin to simplify how to use them, you can use the layers directly.

The layer names follow this pattern:

```
arn:aws:lambda:<region>:534081306603:layer:<layer-name>:<layer-version>

# For example:
arn:aws:lambda:us-east-1:534081306603:layer:php-80:21
```

You can use layers via their full ARN, for example in `serverless.yml`:

```yaml
service: app
provider:
    name: aws
    runtime: provided.al2
functions:
    hello:
        ...
        layers:
            - 'arn:aws:lambda:us-east-1:534081306603:layer:php-80:21'
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
                - 'arn:aws:lambda:us-east-1:534081306603:layer:php-80:21'
```

Bref layers work with AWS Lambda regardless of the tool you use to deploy your application: Serverless, SAM, CloudFormation, Terraform, AWS CDK, etc.

> Remember: the layer ARN contains a region. **You need to use the same region as the rest of your application** else Lambda will not find the layer.

### Layers NPM package

You can use [the `@bref.sh/layers.js` NPM package](https://github.com/brefphp/layers.js) to get up-to-date layer ARNs in Node applications, for example with the AWS CDK.

### Layer version (`<layer-version>`)

The latest of runtime versions can be found at [**runtimes.bref.sh**](https://runtimes.bref.sh/).

Here are the latest versions:

<iframe src="https://runtimes.bref.sh/embedded" class="w-full h-96"></iframe>

You can also find the appropriate ARN/version for your current Bref version by running:

```bash
serverless bref:layers
```

**Watch out:** if you use the layer ARN directly, you may need to update the ARN (the `<version>` part) when you update Bref. Follow the Bref release notes closely.

### Bref ping

Bref layers send a ping to estimate the total number of Lambda invocations powered by Bref. That statistic is useful in two ways:

- to provide new users an idea on how much Bref is used in production
- to communicate to AWS how much Bref is used and push for better PHP integration with AWS Lambda tooling

We consider this to be beneficial both to the Bref project (by getting more users and more consideration from AWS) and for Bref users (more users means a larger community, a stronger and more active project, as well as more features from AWS).

#### What is sent

The data sent in the ping is completely anonymous. It does not contain any identifiable data about anything (the project, users, etc.).

**The only data it contains is:** "A Bref invocation happened with the layer XYZ" (where XYZ is the name of the Bref layer, like "function", "fpm" or "console").

Anyone can inspect the code and the data sent by checking the [`Bref\Runtime\LambdaRuntime::ping()` function](https://github.com/brefphp/bref/blob/master/src/Runtime/LambdaRuntime.php#L374).

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

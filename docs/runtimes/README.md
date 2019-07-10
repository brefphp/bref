---
title: Runtimes
currentMenu: runtimes-introduction
introduction: Bref provides runtimes to bring support for PHP on AWS Lambda.
previous:
    link: /docs/first-steps.html
    title: First steps
next:
    link: /docs/runtimes/function.html
    title: PHP functions
---

There is no built-in support for PHP on AWS Lambda. Instead we need to use 3rd party runtimes via the system of Lambda *layers*.

Bref provides the runtimes (aka layers) needed to run PHP applications, whether they run via HTTP or CLI.

This page is an introduction to the runtimes. The next sections (e.g. PHP functions, HTTP applications) will show how to use them in your project.

## Bref runtimes

### PHP functions: `php-73` and `php-72`

The simplest way to write a lambda is to write one in the form of a PHP function.

This runtime works great for **non-HTTP applications**.

[Read more about the `php-73` runtime here](/docs/runtimes/function.md).

### HTTP applications: `php-73-fpm` and `php-72-fpm`

This runtime uses PHP-FPM to run **HTTP applications** on AWS Lambda.

This runtime is **the easiest to start with**: it works like traditional PHP hosting and is compatible with Symfony and Laravel.

[Read more about the `php-73-fpm` runtime here](/docs/runtimes/http.md).

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
    runtime: provided
plugins:
    - ./vendor/bref/bref
functions:
    hello:
        ...
        layers:
            - ${bref:layer.php-73}
```

The `${...}` notation is the [syntax to use variables](https://serverless.com/framework/docs/providers/aws/guide/variables/) in `serverless.yml`. Bref provides a serverless plugin ("`./vendor/bref/bref`") that provides those variables:

- `${bref:layer.php-73}`
- `${bref:layer.php-72}`
- `${bref:layer.php-73-fpm}`
- `${bref:layer.php-72-fpm}`
- `${bref:layer.console}`

Bref currently provides runtimes for PHP 7.2 and 7.3.

> `php-73` means PHP 7.3.\*. It is not possible to require a specific "patch" version.

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
    runtime: provided
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
            Runtime: provided
            Layers:
                - 'arn:aws:lambda:us-east-1:209497400698:layer:php-73:7'
```

> Remember: the layer ARN contains a region. **You need to use the same region as the rest of your application** else Lambda will not find the layer.

### Layer version (`<layer-version>`)

The latest of runtime versions can be found at [runtimes.bref.sh](https://runtimes.bref.sh/) and is shown below:

<iframe src="https://runtimes.bref.sh/embedded" class="w-full h-96"></iframe>

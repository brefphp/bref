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

The name of the runtimes follow this pattern:

```
arn:aws:lambda:<region>:416566615250:layer:<layer-name>:<layer-version>
```

### Region (`<region>`)

The `<region>` placeholder should contain your application's region. **You need to use the same region as the rest of your application** else Lambda will not find the layer.

### Runtime (`<layer-name>`)

- `php-73`/`php-72`: contains the PHP binary, for [non-HTTP applications](/docs/runtimes/function.md)
- `php-73-fpm`/`php-72-fpm`: contains PHP-FPM for [HTTP applications](/docs/runtimes/http.md)
- `console`: layer that should be used on top of `php-72`/`php-73` to run [console commands](/docs/runtimes/console.md)
- `php-72-loop`: experimental mode, not documented yet

Bref currently provides runtimes for PHP 7.2 and 7.3.

> `php-73` means PHP 7.3.\*. It is not possible to require a specific "patch" version.

### Layer version (`<layer-version>`)

The list of runtime versions is hosted at [runtimes.bref.sh](https://runtimes.bref.sh/) and is shown below:

<iframe src="https://runtimes.bref.sh/embedded" class="w-full h-96"></iframe>

## Usage

To use a runtime you need to import the corresponding layer into your Lambda. For example using AWS SAM:

```yaml
AWSTemplateFormatVersion: '2010-09-09'
Transform: AWS::Serverless-2016-10-31
Resources:
    DemoFunction:
        Type: AWS::Serverless::Function
        Runtime: provided
        Properties:
            [...]
            Layers:
                - '<the layer ARN here>'
```

You can read more about this in the next sections.

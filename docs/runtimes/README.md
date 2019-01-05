---
title: Runtimes
currentMenu: runtimes-introduction
introduction: Bref provides runtimes to bring support for PHP on AWS Lambda.
---

There is no built-in support for PHP on AWS Lambda. Instead we need to use 3rd party runtimes via the system of Lambda *layers*.

Bref provides the runtimes (aka layers) needed to run PHP applications, whether they run via HTTP or CLI.

This page is an introduction to the runtimes. The next sections (e.g. PHP functions, HTTP applications) will show how to use them in your project.

## Bref runtimes

The name of the runtimes follow this pattern:

```
arn:aws:lambda:<region>:416566615250:layer:<layer-name>:<layer-version>
```

### Supported regions (`<region>`)

- `us-east-2`
- other regions will be supported soon

Remember to use the `<region>` that matches your application, else Lambda will not find the layer.

### Runtimes (`<layer-name>`)

- `php-72`: contains the PHP binary, for [non-HTTP applications](/docs/runtimes/function.md)
- `php-72-fpm`: contains PHP-FPM for [HTTP applications](/docs/runtimes/http.md)
- `console`: layer that should be used on top of `php-72` to run [console commands](/docs/runtimes/console.md)
- `php-72-loop`: experimental mode, not documented yet

> `php-72` means PHP 7.2.*. Other versions like PHP 7.3 will be added soon.

### Layer versions

TODO: keep up to date the latest version for each layer. Maybe generate an image via a Lambda?

- `php-72`: 9
- `php-72-fpm`: 4
- `console`: 1

### Examples

- `arn:aws:lambda:us-east-2:416566615250:layer:php-72:9`
- `arn:aws:lambda:us-east-2:416566615250:layer:php-72-fpm:4`

## Usage

To use a runtime you need to import the corresponding layer into your Lambda. For example using AWS SAM:

```yaml
Resources:
    DemoFunction:
        Type: AWS::Serverless::Function
        Properties:
            [...]
            Layers:
                - '<the layer ARN here>'
```

You can read more about this in the next sections.

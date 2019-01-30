---
title: Console applications
currentMenu: console-applications
introduction: Learn how to run serverless console commands on AWS Lambda with Symfony Console or Laravel Artisan.
previous:
    link: /docs/runtimes/http.html
    title: HTTP applications
---

Bref provides a way to run console commands on AWS Lambda.

This can be used to run the [Symfony Console](https://symfony.com/doc/master/components/console.html), [Silly](https://github.com/mnapoli/silly) or Laravel Artisan commands in production.

## Configuration

The lambda function used for running console applications must use two Lambda layers:

- the base PHP layer (the PHP runtime that provides the `php` binary)
- the "console" layer that overrides the base runtime to execute your console application

Below is a minimal `template.yaml`. To create it automatically run `vendor/bin/bref init` and select "Console application".

```yaml
AWSTemplateFormatVersion: '2010-09-09'
Transform: AWS::Serverless-2016-10-31
Resources:
    MyFunction:
        Type: AWS::Serverless::Function
        Properties:
            FunctionName: 'my-function'
            CodeUri: .
            Handler: bin/console # or `artisan` for Laravel
            Runtime: provided
            Layers:
                # PHP runtime
                - 'arn:aws:lambda:<region>:416566615250:layer:php-73:<version>'
                # Console layer
                - 'arn:aws:lambda:<region>:416566615250:layer:console:<version>'
```

## Usage

To run a console command on AWS Lambda use `bref cli`:

```bash
vendor/bin/bref cli <function-name> -- <command>
```

`<function-name>` is the name of the function defined in `template.yaml`. In our example above that would be `my-function`.

Pass your command, arguments and options by putting them after `--`. The `--` delimiter separates between options for the `bref cli` command (before `--`) and your command (after `--`).

```bash
vendor/bin/bref cli my-function <bref options> -- <your command, your options>
```

For example:

```bash
# Runs the CLI application without arguments and displays the help
$ vendor/bin/bref cli my-function
# ...

$ vendor/bin/bref cli my-function -- doctrine:migrate
Your database will be migrated.
To execute the SQL queries run the command with the `--force` option.

$ vendor/bin/bref cli my-function -- doctrine:migrate --force
Your database has been migrated.
```

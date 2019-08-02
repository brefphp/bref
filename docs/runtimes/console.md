---
title: Console applications
current_menu: console-applications
introduction: Learn how to run serverless console commands on AWS Lambda with Symfony Console or Laravel Artisan.
previous:
    link: /docs/runtimes/http.html
    title: HTTP applications
---

Bref provides a way to run console commands on AWS Lambda.

This can be used to run the [Symfony Console](https://symfony.com/doc/master/components/console.html), [Silly](https://github.com/mnapoli/silly) or [Laravel Artisan](https://laravel.com/docs/5.8/artisan) commands in production.

## Configuration

The lambda function used for running console applications must use two Lambda layers:

- the [base PHP layer](function.md) (the PHP runtime that provides the `php` binary)
- the "console" layer that overrides the base runtime to execute your console application

Below is a minimal `serverless.yml`. To create it automatically run `vendor/bin/bref init` and select "Console application".

```yaml
service: app
provider:
    name: aws
    runtime: provided
plugins:
    - ./vendor/bref/bref
functions:
    hello:
        handler: bin/console # or `artisan` if you are using Laravel
        layers:
            - ${bref:layer.php-73} # PHP runtime
            - ${bref:layer.console} # Console layer
```

## Usage

To run a console command on AWS Lambda use `bref cli`:

```bash
vendor/bin/bref cli <function-name> -- <command>
```

`<function-name>` is the name of the function that was deployed on AWS. In our example above that would be `hello-dev` because Serverless adds the stage (by default `dev`) as a suffix.

Pass your command, arguments and options by putting them after `--`. The `--` delimiter separates between options for the `bref cli` command (before `--`) and your command (after `--`).

```bash
vendor/bin/bref cli hello-dev <bref options> -- <your command, your options>
```

For example:

```bash
# Runs the CLI application without arguments and displays the help
$ vendor/bin/bref cli hello-dev
# ...

$ vendor/bin/bref cli hello-dev -- doctrine:migrate
Your database will be migrated.
To execute the SQL queries run the command with the `--force` option.

$ vendor/bin/bref cli hello-dev -- doctrine:migrate --force
Your database has been migrated.

# Use environment variables to configure your AWS credentials
$ AWS_DEFAULT_REGION=eu-central-1 AWS_ACCESS_KEY_ID=foo AWS_SECRET_ACCESS_KEY=bar vendor/bin/bref cli my-function
# ...
```

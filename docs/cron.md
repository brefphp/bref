---
title: Cron tasks with PHP on AWS Lambda
current_menu: cron
introduction: Learn how to create serverless cron tasks with PHP on AWS Lambda.
---

AWS Lambda allows us to run tasks based on a cron schedule. We can run:

- CLI commands
- PHP functions

This page will present both approaches.

In both cases, we will use [Serverless's `schedule` option](https://www.serverless.com/framework/docs/providers/aws/events/schedule/) to setup the cron tab.

## CLI cron tasks

It is possible to run CLI commands as cron tasks.

These CLI commands are running on AWS Lambda using [Bref's Console runtime](/docs/runtimes/console.md), please read about it first.

```yaml
functions:
    console:
        handler: bin/console # or `artisan` if you are using Laravel
        layers:
            - ${bref:layer.php-73} # PHP runtime
            - ${bref:layer.console} # Console layer
        events:
            - schedule:
                  rate: rate(1 hour)
                  input: '"list --verbose"'
```

The example above will run the `bin/console list --verbose` command every hour in AWS Lambda.

> Note that the command is configured as a double quoted string: `'"command"'`.
> Why? The `input` value needs to be valid JSON, which means that it needs to be a JSON string, stored in a YAML string.

## Function cron tasks

It is possible to run PHP functions as cron tasks.

These PHP functions are running on AWS Lambda using [Bref's Function runtime](/docs/runtimes/function.md), please read about it first.

```yaml
functions:
    console:
        handler: function.php
        layers:
            - ${bref:layer.php-73}
        events:
            - schedule: rate(1 hour)
```

The example above will run `function.php` every hour in AWS Lambda.

It is possible to provide data inside the `$event` variable via the `input` option:

```yaml
    console:
        ...
        events:
            - schedule:
                  rate: rate(1 hour)
                  input:
                      foo: bar
                      Hello: World
```

Read more about this in the [Serverless `schedule` documentation](https://www.serverless.com/framework/docs/providers/aws/events/schedule/).

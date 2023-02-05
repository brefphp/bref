---
title: CLI cron tasks on AWS Lambda
current_menu: web-cron
introduction: Learn how to create CLI serverless cron tasks with PHP on AWS Lambda.
previous:
    link: /docs/runtimes/console.html
    title: Console commands
next:
    link: /docs/web-apps/local-development.html
    title: Local development for web apps
---

AWS Lambda lets us run [console commands](/docs/runtimes/console.md) as cron tasks using the `schedule` event:

```yaml
functions:
    cron:
        handler: bin/console # or 'artisan' for Laravel
        runtime: php-81-console
        events:
            - schedule:
                  rate: rate(1 hour)
                  input: '"list --verbose"'
```

The example above will run the `bin/console list --verbose` command every hour in AWS Lambda.

Note that the command **is a double quoted string**: `'"command"'`.
Why? The `input` value needs to be valid JSON, which means that it needs to be a JSON string, stored in a YAML string.

Read more about the `schedule` feature in the [Serverless documentation](https://www.serverless.com/framework/docs/providers/aws/events/schedule/).

> If you are interested in **cron functions** instead, read the [Cron functions](/docs/function/cron.html) documentation.

---
title: Cron functions on AWS Lambda
current_menu: cron-function
introduction: Learn how to create serverless cron functions with PHP on AWS Lambda.
previous:
    link: /docs/function/local-development.html
    title: Local development
---

AWS Lambda lets us run [PHP functions](/docs/runtimes/function.md) as cron tasks using the `schedule` event:

```yaml
functions:
    console:
        handler: function.php
        runtime: php-81
        events:
            - schedule: rate(1 hour)
```

The example above will run the function returned by `function.php` every hour in AWS Lambda. For example:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

return function ($event) {
    echo 'My cron function is running!';
};
```

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

Read more about the `schedule` feature in the [Serverless documentation](https://www.serverless.com/framework/docs/providers/aws/events/schedule/).

> If you are interested in **cron CLI commands** instead, read the [Cron commands](/docs/web-apps/cron.html) documentation.

---
title: Local development for functions
current_menu: function-local-development
previous:
    link: /docs/function/handlers.html
    title: Typed handlers
next:
    link: /docs/function/cron.html
    title: Cron functions
---

It is possible to run **PHP functions** locally.

> To run **web apps** locally, see [Local development for HTTP applications](/docs/web-hosting/local-development.md) instead.

The `vendor/bin/bref local` command invokes your [PHP functions](/docs/runtimes/function.md) locally. You can provide an event if your function expects one.

For example, given this function:

```php
return function (array $event) {
    return 'Hello ' . ($event['name'] ?? 'world');
};
```

```yaml
# ...

functions:
    hello:
        handler: my-function.php
        layers:
            - ${bref:layer.php-74}
```

You can invoke it with or without event data:

```bash
$ vendor/bin/bref local hello
Hello world

# With JSON event data
$ vendor/bin/bref local hello '{"name": "Jane"}'
Hello Jane

# With JSON in a file
$ vendor/bin/bref local hello --file=event.json
Hello Jane
```

The `bref local` command runs using the local PHP installation. If you prefer to run commands using the same environment as Lambda, you can use Docker.

Here is an example, feel free to adjust it to fit your needs:

```bash
docker run --rm -it -v $(PWD):/var/task:ro,delegated bref/php-74 vendor/bin/bref local hello
```

If you do not use `serverless.yml` but something else like SAM/CDK/CloudFormation/Terraform, use the `--handler` parameter instead:

```bash
$ vendor/bin/bref local --handler=my-function.php
Hello world

# With JSON event data
$ vendor/bin/bref local '{"name": "Jane"}' --handler=my-function.php
Hello Jane

# With JSON in a file
$ vendor/bin/bref local --handler=my-function.php --file=event.json
Hello Jane
```

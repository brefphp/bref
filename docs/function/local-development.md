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

> **Warning:**
> To run **web apps** locally, see [Local development for HTTP applications](/docs/web-apps/local-development.md) instead.

## With Serverless Framework

The `serverless bref:local` command invokes your [PHP functions](/docs/runtimes/function.md) locally, using PHP installed on your machine. You can provide an event if your function expects one.

> **Note:**
> The `serverless bref:local` command is a simpler alternative to the native `serverless invoke local` command, which tries to run PHP using Docker with very little success. Use `bref:local` instead of `invoke local`.

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
        runtime: php-81
```

You can invoke it with or without event data:

```bash
$ serverless bref:local -f hello
Hello world

# With JSON event data
$ serverless bref:local -f hello --data '{"name": "Jane"}'
Hello Jane

# With JSON in a file
$ serverless bref:local -f hello --path=event.json
Hello Jane
```

> **Note:** On Windows PowerShell, you must escape the "double quote" char if you write JSON directly in the CLI. Example: 
> ```bash
> $ serverless bref:local -f hello --data '{\"name\": \"Bill\"}'
> ```

The `serverless bref:local` command runs using the local PHP installation. If you prefer to use Docker, check out the "Without Serverless Framework" section below.

## API Gateway local development

If you build HTTP applications with [API Gateway HTTP events](handlers.md#api-gateway-http-events), `serverless bref:local` is a bit unpractical because you need to manually craft HTTP events in JSON.

Instead, you can use the [`bref/dev-server`](https://github.com/brefphp/dev-server) package to emulate API Gateway locally.

## Without Serverless Framework

If you do not use `serverless.yml` but something else, like SAM/AWS CDK/Terraform, use the `vendor/bin/bref-local` command instead:

```bash
$ vendor/bin/bref-local <handler> <event-data>

# For example
$ vendor/bin/bref-local my-function.php
Hello world

# With JSON event data
$ vendor/bin/bref-local my-function.php '{"name": "Jane"}'
Hello Jane
```

If you want to run your function in Docker:

```bash
$ docker run --rm -it --entrypoint= -v $(PWD):/var/task:ro bref/php-81:2 vendor/bin/bref-local my-function.php

# You can also use the `dev` images for a simpler command (and Xdebug and Blackfire in the image):
$ docker run --rm -it -v $(PWD):/var/task:ro bref/php-81-fpm-dev:2 vendor/bin/bref-local my-function.php
```

You can also use Docker Compose:

```yaml
version: "3.5"
services:
    app:
        image: bref/php-81-fpm-dev:2
        volumes:
            - .:/var/task
```

Then run functions:

```bash
$ docker-compose run app vendor/bin/bref-local my-function.php
```

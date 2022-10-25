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

> To run **web apps** locally, see [Local development for HTTP applications](/docs/web-apps/local-development.md) instead.

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
        layers:
            - ${bref:layer.php-81}
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

The `serverless bref:local` command runs using the local PHP installation. If you prefer to run commands using the same environment as Lambda, you can use Docker.

Here is an example, feel free to adjust it to fit your needs:

```bash
# TODO needs to be adjusted
docker run --rm -it --entrypoint= -v $(PWD):/var/task:ro bref/php-74 vendor/bin/bref local hello
```

If you do not use `serverless.yml` but something else like SAM/CDK/CloudFormation/Terraform, use the `--handler` parameter instead:

```bash
# TODO needs to be adjusted
$ vendor/bin/bref local --handler=my-function.php
Hello world

# With JSON event data
$ vendor/bin/bref local '{"name": "Jane"}' --handler=my-function.php
Hello Jane

# With JSON in a file
$ vendor/bin/bref local --handler=my-function.php --file=event.json
Hello Jane
```

## API Gateway local development

If you build HTTP applications with [API Gateway HTTP events](handlers.md#api-gateway-http-events), `serverless bref:local` is a bit unpractical because you need to manually craft HTTP events in JSON.

Instead, you can use the [`bref/dev-server`](https://github.com/brefphp/dev-server) package to emulate API Gateway locally.

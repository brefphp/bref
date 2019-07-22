---
title: Local development
current_menu: local-development
---

To run your applications locally with an architecture close to production you can use the `sam` command line tool from AWS.

## PHP functions

The `serverless invoke local` command invokes your [PHP functions](/docs/runtimes/function.md) locally. It is necessary to use the `--docker` option so that Bref layers are correctly downloaded. You can provide an event if your function expects one.

For example, given this function:

```php
lambda(function (array $event) {
    return 'Hello ' . ($event['name'] ?? 'world');
});
```

```yaml
# ...

functions:
    myFunction:
        handler: index.php
        layers:
            - ${bref:layer.php-73}
```

You can invoke it with or without event data:

```sh
$ serverless invoke local --docker -f myFunction
Hello world

$ serverless invoke local --docker -f myFunction --data '{"name": "Jane"}'
Hello John
```

> Learn more in the [`serverless invoke local` documentation](https://serverless.com/framework/docs/providers/aws/cli-reference/invoke-local/) or run `serverless invoke local --help`.

## HTTP applications

If you want to keep things simple, you can run your PHP application like you did without Bref. For example with your favorite framework:

- Laravel via `php artisan serve` or [Homestead](https://laravel.com/docs/5.7/homestead) or [Laravel Valet](https://laravel.com/docs/5.7/valet)
- Symfony via `php bin/console server:start` ([documentation](https://symfony.com/doc/current/setup/built_in_web_server.html))

If you are not using any framework, you can use PHP's built-in server:

```bash
php -S localhost:8000 index.php
# The application is now available at http://localhost:8000/
```

In order to run the application locally in an environment closer to production, we are working on [Docker images in #237](https://github.com/brefphp/bref/issues/237).

There is also a [Serverless plugin called "serverless-offline"](https://github.com/dherault/serverless-offline) that runs API Gateway locally. However it currently doesn't support layers, which means it doesn't work with Bref yet. We are working on this in [#648](https://github.com/dherault/serverless-offline/pull/648).

## Console applications

Console applications can be tested just like before: by running the command in your terminal.

For example with Symfony you can run `bin/console <your-command>` , or with Laravel run `php artisan <your-command>`.

If you want to run your console in an environment close to production, you can use the Bref Docker images. Here is an example of a `docker-compose.yml` file:

```yaml
console:
    image: bref/php-73
    volumes:
        - .:/var/task:ro # Read only, like a lambda function
        # Some directories can be made writable for the development environment:
        # - ./cache:/var/task/cache
    command: php /var/task/bin/console
```

Then commands can be run via:

```bash
docker-compose run console php /var/task/bin/console <your-command>
```

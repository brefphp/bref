---
title: Local development
currentMenu: local-development
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

The `sam local start-api` command starts Docker containers that will emulate AWS Lambda and API Gateway on your machine:

```sh
sam local start-api
```

Once started, your application will be available at [http://localhost:3000](http://localhost:3000/).

> Learn more in the [`sam local` documentation](https://github.com/awslabs/aws-sam-cli/blob/develop/docs/usage.rst#run-api-gateway-locally) or run `sam local start-api --help`.

If you want to keep things simple, remember that you can still run your PHP application like you did without Bref. For example with your favorite framework:

- Laravel via `php artisan serve` or [Homestead](https://laravel.com/docs/5.7/homestead) or [Laravel Valet](https://laravel.com/docs/5.7/valet)
- Symfony via `php bin/console server:start` ([documentation](https://symfony.com/doc/current/setup/built_in_web_server.html))

Using SAM is useful to test your application in an environment close to production.

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

# Local development

To run your applications locally with an architecture close to production you can use the `sam` command line tool from AWS.

## PHP functions

The `sam local invoke` command invokes your function in a Docker container. You can provide an event if your function expects one.

For example, given this function:

```php
λ(function ($event) {
    return 'Hello ' . ($event['name'] ?? 'world!');
});
```

You can invoke it with or without event data:

```sh
$ sam local invoke --no-event
Hello world!

$ echo '{"name": "John" }' | sam local invoke
Hello John
```

> Learn more in the [`sam local` documentation](https://github.com/awslabs/aws-sam-cli/blob/develop/docs/usage.rst#invoke-functions-locally) or run `sam local invoke --help`.

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

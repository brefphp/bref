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

In order to run the application locally in an environment closer to production, you can use the [Bref Docker images](https://cloud.docker.com/u/bref). For example for an HTTP application, create the following `docker-compose.yml`:

```yaml
version: "2.1"

services:
    web:
        image: bref/fpm-dev-gateway
        ports:
            - '8000:80'
        volumes:
            - .:/var/task
        links:
            - php
        environment:
            HANDLER: index.php
    php:
        image: bref/php-73-fpm-dev
        volumes:
            - .:/var/task:ro
```

After running `docker-compose up`, the application will be available at [http://localhost:8000/](http://localhost:8000/).

The `HANDLER` environment variable lets you define which PHP file will be handling all HTTP requests. This should be the same handler that you have defined in `serverless.yml` for your HTTP function.

> Currently the Docker image support only one PHP handler. If you have multiple HTTP functions in `serverless.yml`, you can duplicate the service in `docker-compose.yml` to have one container per lambda function.

### Read-only filesystem

The code will be mounted as read-only in `/var/task`, just like in Lambda. However when developing locally, it is common to regenerate cache files on the fly (for example Symfony or Laravel cache). You have 2 options:

- mount the whole codebase as writable:

    ```yaml
        volumes:
            - .:/var/task
    ```
- mount a specific cache directory as writable (better):

    ```yaml
        volumes:
            - .:/var/task:ro
            - ./cache:/var/task/cache
    ```

### Assets

If you want to serve assets locally, you can define a `DOCUMENT_ROOT` environment variable:

```yaml
version: "2.1"

services:
    web:
        image: bref/fpm-dev-gateway
        ports:
            - '8000:80'
        volumes:
            - .:/var/task
        links:
            - php
        environment:
            HANDLER: public/index.php
            DOCUMENT_ROOT: public
    php:
        image: bref/php-73-fpm-dev
        volumes:
            - .:/var/task:ro
```

In the example above, a `public/assets/style.css` file will be accessible at `http://localhost:8000/assets/style.css`.

> Be aware that serving assets in production will not work like this out of the box. You will need [to use a S3 bucket](/docs/runtimes/http.md#assets).

## Console applications

Console applications can be tested just like before: by running the command in your terminal.

For example with Symfony you can run `bin/console <your-command>` , or with Laravel run `php artisan <your-command>`.

If you want to run your console in an environment close to production, you can use the Bref Docker images. Here is an example of a `docker-compose.yml` file:

```yaml
version: "2.1"

services:
    console:
        image: bref/php-73
        volumes:
            - .:/var/task:ro
        entrypoint: php
```

Then commands can be run via:

```bash
docker-compose run console php bin/console <your-command>
```

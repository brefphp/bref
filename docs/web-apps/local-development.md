---
title: Local development for web apps
current_menu: web-local-development
previous:
    link: /docs/web-apps/cron.html
    title: Cron commands
next:
    link: /docs/web-apps/docker.html
    title: Docker
---

It is possible to run **web applications** locally.

> To run event-driven **PHP functions** locally, see [Local development for PHP Functions](/docs/function/local-development.md) instead.

## The simple way

To keep things simple, you can run your PHP application like you did without Bref. For example with your favorite framework:

- Laravel via `php artisan serve` or [Homestead](https://laravel.com/docs/homestead) or [Laravel Valet](https://laravel.com/docs/valet)
- Symfony via `symfony server:start` ([documentation](https://symfony.com/doc/current/setup/symfony_server.html))

If you are not using any framework, you can use PHP's built-in server:

```bash
php -S localhost:8000
# The application is now available at http://localhost:8000/
```

## Docker

In order to run the application locally in an environment closer to production, you can use the [Bref Docker images](https://hub.docker.com/u/bref). For example, create the following `docker-compose.yml`:

```yaml
version: "3.5"

services:
    app:
        image: bref/php-81-fpm-dev:2
        ports: [ '8000:8000' ]
        volumes:
            - .:/var/task
        environment:
            HANDLER: public/index.php
```

After running `docker-compose up`, the application will be available at [http://localhost:8000/](http://localhost:8000/).

The `HANDLER` environment variable lets you define which PHP file will be handling all HTTP requests. This should be the same handler that you have defined in `serverless.yml` for your HTTP function.

> Currently, the Docker image support only one PHP handler. If you have multiple HTTP functions in `serverless.yml`, you can duplicate the service in `docker-compose.yml` to have one container per lambda function.

### Read-only filesystem

The code will be mounted in `/var/task`, just like in Lambda. But in Lambda, `/var/task` is read-only.

When developing locally, it is common to regenerate cache files on the fly (for example Symfony or Laravel cache). You have 2 options:

- mount the whole codebase as writable:

    ```yaml
            volumes:
                - .:/var/task
    ```
- mount a specific cache directory as writable (better):

    ```yaml
            volumes:
                - .:/var/task:ro
                - ./storage:/var/task/storage
    ```

### Assets

If you want to serve assets locally, you can define a `DOCUMENT_ROOT` environment variable:

```yaml
version: "3.5"

services:
    app:
        image: bref/php-81-fpm-dev:2
        ports: [ '8000:8000' ]
        volumes:
            - .:/var/task
        environment:
            HANDLER: public/index.php
            DOCUMENT_ROOT: public
```

In the example above, a `public/assets/style.css` file will be accessible at `http://localhost:8000/assets/style.css`.

> Be aware that serving assets in production will not work like this out of the box. You will need [to use a S3 bucket](/docs/runtimes/http.md#assets).

### Xdebug

The development container (`bref/php-<version>-fpm-dev`) comes with Xdebug pre-installed.

To enable it, create a `php/conf.dev.d/php.ini` file in your project containing:

```ini
zend_extension=xdebug.so
```

Now start the debug session by issuing a request to your application [in the browser](https://xdebug.org/docs/remote#starting).

#### Xdebug and MacOS

Docker for Mac uses a virtual machine for running docker. That means you need to use a special host name (`host.docker.internal`) that is mapped to the host machine's IP address.

Edit the `php/conf.dev.d/php.ini` file:

```ini
zend_extension=xdebug.so

[xdebug]
xdebug.remote_enable = 1
xdebug.remote_autostart = 0
xdebug.remote_host = 'host.docker.internal'
```

### Blackfire

The development container (`bref/php-<version>-fpm-dev`) comes with the [blackfire](https://www.blackfire.io/) extension. When using docker compose, you can add the following service for the blackfire agent:

```yaml
services:
    blackfire:
        image: blackfire/blackfire
        environment:
            BLACKFIRE_SERVER_ID: server-id
            BLACKFIRE_SERVER_TOKEN: server-token
```

In order to enable the probe you can create a folder `php/conf.dev.d` in your project and include an ini file enabling blackfire:

```ini
extension=blackfire
blackfire.agent_socket=tcp://blackfire:8707
```

For more details about using blackfire in a docker environment see the [blackfire docs](https://blackfire.io/docs/integrations/docker)

## Console applications

Console applications can be tested just like before: by running the command in your terminal.

For example with Symfony you can run `bin/console <your-command>` , or with Laravel run `php artisan <your-command>`.

If you want to run your console in an environment close to production, you can use the Bref Docker dev images documented above. For example, if you have a `docker-compose.yml` file like this:

```yaml
version: "3.5"

services:
    app:
        image: bref/php-81-fpm-dev:2
        ports: [ '8000:8000' ]
        volumes:
            - .:/var/task
        environment:
            HANDLER: public/index.php
```

Then CLI commands can be run in Docker via:

```bash
# Symfony (bin/console)
docker-compose run app php bin/console <your-command>

# Laravel (artisan)
docker-compose run app php artisan <your-command>
```

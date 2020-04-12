---
title: Serverless Laravel applications
current_menu: laravel
introduction: Learn how to deploy serverless Laravel applications on AWS Lambda using Bref.
---

This guide helps you run Laravel applications on AWS Lambda using Bref. These instructions are kept up to date to target the latest Laravel version.

A demo application is available on GitHub at [github.com/brefphp/examples](https://github.com/brefphp/examples).

## Setup

Assuming your are in existing Laravel project, let's install Bref via Composer:

```
composer require bref/bref
```

Then let's create a `serverless.yml` configuration file (at the root of the project) optimized for Laravel:

```yaml
service: bref-demo-laravel

provider:
    name: aws
    region: us-east-1
    runtime: provided

plugins:
    - ./vendor/bref/bref

package:
  exclude:
    - node_modules/**
    - public/storage
    - resources/assets/**
    - storage/**
    - tests/**

functions:
    website:
        handler: public/index.php
        timeout: 28 # in seconds (API Gateway has a timeout of 29 seconds)
        layers:
            - ${bref:layer.php-73-fpm}
        events:
            -   http: 'ANY /'
            -   http: 'ANY /{proxy+}'
    artisan:
        handler: artisan
        timeout: 120 # in seconds
        layers:
            - ${bref:layer.php-73} # PHP
            - ${bref:layer.console} # The "console" layer
```

We will also need to customize the location for compiled views, as well as customize a few variables in the `.env` file:

```dotenv
VIEW_COMPILED_PATH=/tmp/storage/framework/views

# We cannot store sessions to disk: if you don't need sessions (e.g. API) then use `array`
# If you write a website, use `cookie` or store sessions in database.
SESSION_DRIVER=cookie

# Logging to stderr allows the logs to end up in Cloudwatch
LOG_CHANNEL=stderr
```

Finally we need to edit `app/Providers/AppServiceProvider.php` because Laravel will not create that directory automatically:

```php
    public function boot()
    {
        // Make sure the directory for compiled views exist
        if (! is_dir(config('view.compiled'))) {
            mkdir(config('view.compiled'), 0755, true);
        }
    }
```

## Deployment

At the moment deploying Laravel with its caches will break in AWS Lambda (because most file paths are different). This is why it is currently necessary to deploy without the config cache file. Simply run `php artisan config:clear` to make sure that file doesn't exist.

Your application is now ready to be deployed. Follow [the deployment guide](/docs/deploy.md).

## Troubleshooting

In case your application is showing a blank page after being deployed, [have a look at the logs](../environment/logs.md).

If you get the following error:

> production.ERROR: mkdir(): Invalid path {"exception":"[object] (ErrorException(code: 0): mkdir(): Invalid path at /var/task/app/Providers/AppServiceProvider.php:20)"

then check the file `config/view.php` and make sure the `'compiled'` entry looks like this:

```php
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),
```

## Laravel Artisan

As you may have noticed, we define a function of type "console" in `serverless.yml`. That function is using the [Console runtime](/docs/runtimes/console.md), which lets us run Laravel Artisan on AWS Lambda.

For example, to execute an `artisan` command on Lambda for the above configuration, run the below command.

```
vendor/bin/bref cli bref-demo-laravel-artisan <bref options> -- <your command, your options>
```

For more details follow [the "Console" guide](/docs/runtimes/console.md).

## Laravel Passport

Laravel Passport has a `passport:install` command. However, this command cannot be run in Lambda because it needs to write files to the `storage` directory.

Instead, here is what you need to do:

- Run `php artisan passport:keys` locally to generate key files.

    This command will generate the `storage/oauth-private.key` and `storage/oauth-public.key` files.

- Instead of deploying those 2 files, we will put the content of these files as environment variables in the `.env` that will be deployed.

    Why? Deploying those files can get complex: you don't want to commit them in git (because they are sensible), so deploying them from CI will be hard. Using environment variables is a bit simpler.

    To convert line returns to `\n`, run the following commands:
    
    ```bash
    cat storage/oauth-private.key | tr -d '\r' | perl -p -e 's/\n/\\n/g'
    cat storage/oauth-public.key | tr -d '\r' | perl -p -e 's/\n/\\n/g'
    ```
  
    Copy the result of those commands into `.env` (and make sure each variable begins and ends with the pattern below):
    
    ```env
    PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----\nMIIJJwIBAAKCAgEAw3KPag...\n-----END RSA PRIVATE KEY-----"
    PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOC...\n-----END PUBLIC KEY-----"
    ```
  
    The `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` variables are automatically used by Laravel Passport when they exist. That means we don't need to deploy the `storage/oauth-private.key` and `storage/oauth-public.key` files anymore.

- You can now deploy the application with that new `.env` file:

    ```yaml
    serverless deploy
    ```

- Finally, we can create the tokens (which is the second part of the `passport:install` command):

   ```bash
   vendor/bin/bref cli <artisan-function-name> -- passport:client --personal --name 'Laravel Personal Access Client'
   vendor/bin/bref cli <artisan-function-name> -- passport:client --password --name 'Laravel Personal Access Client'
   ```

All these steps were replacements of running the `passport:install` command [from the Passport documentation](https://laravel.com/docs/7.x/passport#installation).

---
title: Serverless Laravel applications
current_menu: laravel
introduction: Learn how to deploy serverless Laravel applications on AWS Lambda using Bref.
---

This guide helps you run Laravel applications on AWS Lambda using Bref. These instructions are kept up to date to target the latest Laravel version.

A demo application is available on GitHub at [github.com/mnapoli/bref-laravel-demo](https://github.com/mnapoli/bref-laravel-demo).

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
    environment:
        # Laravel environment variables
        APP_STORAGE: '/tmp'

plugins:
    - ./vendor/bref/bref

functions:
    website:
        handler: public/index.php
        timeout: 28 # in seconds (API Gateway has a timeout of 28 seconds)
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

Now we still have a few modifications to do on the application to make it compatible with AWS Lambda.

Since [the filesystem is readonly](/docs/environment/storage.md) except for `/tmp` we need to customize where the cache files are stored. Add this line in `bootstrap/app.php` after `$app = new Illuminate\Foundation\Application`:

```php
/*
 * Allow overriding the storage path in production using an environment variable.
 */
$app->useStoragePath($_ENV['APP_STORAGE'] ?? $app->storagePath());
```

We will also need to customize the location for compiled views, as well as customize a few variables in the `.env` file:

```dotenv
VIEW_COMPILED_PATH=/tmp/storage/framework/views

# We cannot store sessions to disk: if you don't need sessions (e.g. API)
# then use `array`, else store sessions in database or cookies
SESSION_DRIVER=array

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

## Laravel Artisan

As you may have noticed, we define a function of type "console" in `serverless.yml`. That function is using the [Console runtime](/docs/runtimes/console.md), which lets us run Laravel Artisan on AWS Lambda.

To use it follow [the "Console" guide](/docs/runtimes/console.md).

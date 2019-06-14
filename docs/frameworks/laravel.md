---
title: Serverless Laravel applications
currentMenu: laravel
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

> Make sure to set all the `Layers` regions to the same region you are deploying into. Otherwise you will see a GetObject error while deploying the stack.
>
> For example if deploying in London you would change `us-east-1` to `eu-west-2`

```yaml
service: bref-demo-laravel

provider:
    name: aws
    runtime: provided
    environment:
        # Laravel environment variables
        APP_STORAGE: '/tmp'

functions:
    website:
        handler: public/index.php
        timeout: 30 # in seconds (API Gateway has a timeout of 30 seconds)
        layers:
            - 'arn:aws:lambda:us-east-1:209497400698:layer:php-73-fpm:6'
        events:
            -   http: 'ANY /'
            -   http: 'ANY {proxy+}'
    artisan:
        handler: artisan
        timeout: 120 # in seconds
        layers:
            - 'arn:aws:lambda:us-east-1:209497400698:layer:php-73:6' # PHP
            - 'arn:aws:lambda:us-east-1:209497400698:layer:console:6' # The "console" layer
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

Your application is now ready to be deployed. Follow [the deployment guide](/docs/deploy.md#deploying-with-sam).

## Laravel Artisan

As you may have noticed, we define a function of type "console" in `template.yaml`. That function is using the [Console runtime](/docs/runtimes/console.md), which lets us run Laravel Artisan on AWS Lambda.

To use it follow [the "Console" guide](/docs/runtimes/console.md).

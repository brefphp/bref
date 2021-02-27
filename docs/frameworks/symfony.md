---
title: Serverless Symfony applications
current_menu: symfony
introduction: Learn how to deploy serverless Symfony applications on AWS Lambda using Bref.
---

This guide helps you run Symfony applications on AWS Lambda using Bref. These instructions are kept up-to-date to be compatible with the latest Symfony version.

A demo application is available on GitHub at [github.com/mnapoli/bref-symfony-demo](https://github.com/mnapoli/bref-symfony-demo).

## Setup

Assuming we're inside an existing Symfony project, let's install Bref via Composer:

```
composer require bref/bref
```

Now we can create a `serverless.yml` configuration file (at the root of the project) optimized for Symfony:

```yaml
service: bref-demo-symfony

provider:
    name: aws
    region: us-east-1
    runtime: provided.al2
    environment:
        # Symfony environment variables
        APP_ENV: prod

plugins:
    - ./vendor/bref/bref

package:
    exclude:
        - node_modules/**
        - tests/**
        - var/**

functions:
    website:
        handler: public/index.php
        timeout: 28 # in seconds (API Gateway has a timeout of 29 seconds)
        layers:
            - ${bref:layer.php-74-fpm}
        events:
            - httpApi: '*'
    console:
        handler: bin/console
        timeout: 120 # in seconds
        layers:
            - ${bref:layer.php-74} # PHP
            - ${bref:layer.console} # The "console" layer
```

We still have a few modifications to do on the application to make it compatible with AWS Lambda.

Since [the filesystem is readonly](/docs/environment/storage.md) except for `/tmp` we need to customize where the cache and the logs are stored in the `src/Kernel.php` file. This is done by adding 2 new methods to the `Kernel` class:

```php
    public function getLogDir()
    {
        // When on the lambda only /tmp is writeable
        if (isset($_SERVER['LAMBDA_TASK_ROOT'])) {
            return '/tmp/log/';
        }

        return parent::getLogDir();
    }

    public function getCacheDir()
    {
        // When on the lambda only /tmp is writeable
        if (isset($_SERVER['LAMBDA_TASK_ROOT'])) {
            return '/tmp/cache/'.$this->environment;
        }

        return parent::getCacheDir();
    }
```

## Deploy

The application is now ready to be deployed. Follow [the deployment guide](/docs/deploy.md).

## Console

As you may have noticed, we define a function of type "console" in `serverless.yml`. That function is using the [Console runtime](/docs/runtimes/console.md), which lets us run the Symfony Console on AWS Lambda.

To use it follow [the "Console" guide](/docs/runtimes/console.md).

## Logs

While overriding the log's location in the `Kernel` class was necessary for Symfony to run correctly, by default Symfony logs in `stderr`. That is great because Bref [automatically forwards `stderr` to AWS CloudWatch](/docs/environment/logs.md).

However if the application is using Monolog we need to configure Monolog to log into `stderr` as well:

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        # ...
        nested:
            type: stream
            path: "php://stderr"
```

Be aware that Symfony also logs deprecations:

```yaml
monolog:
    handlers:
        # ...
        deprecation:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.deprecations.log"
```

We should either change the path to `php://stderr`, or remove the logging of deprecations entirely.

## Environment variables

Since Symfony 4, the production parameters are configured through environment variables. We can define them in `serverless.yml`.

```yaml
provider:
    environment:
         APP_ENV: prod
```

The secrets (e.g. database passwords) must however not be committed in this file.

To learn more about all this, read the [environment variables documentation](/docs/environment/variables.md).

## Assets

To deploy Symfony websites, we need assets to be served by AWS S3. Setting up the S3 bucket is already explained in the [websites documentation](../websites.md#hosting-static-files-with-s3). This section provides additional instructions specific to Symfony assets and Webpack Encore.

First, you need to [tell Symfony](https://symfony.com/doc/current/reference/configuration/framework.html#base-urls) to use the S3 URL as the assets base URL, instead of your app domain in production.

```yaml
# config/packages/prod/framework.yaml
framework:
  assets:
    base_urls: 'https://<bucket-name>.s3.amazonaws.com'
```

If using Webpack Encore, you also need to add the following config at the end of `webpack.config.js`

```js
if (Encore.isProduction()) {
    Encore.setPublicPath('https://<bucket-name>.s3.amazonaws.com');
}
```

Finally, you can compile assets for production in the `public` directory, then synchronize that directory to a S3 bucket:

```bash
php bin/console assets:install --env prod
# if using Webpack Encore, additionally run
yarn encore production
aws s3 sync public/ s3://<bucket-name>/ --delete --exclude index.php
```

### Assets in templates

For the above configuration to work, assets must be referenced in templates via the `asset()` helper:

```html
<script src="{{ asset('js/app.js') }}"></script>
```

If your templates reference some assets via direct path, you should edit them to use the `asset()` helper:

```html
- <img src="/images/logo.png">
+ <img src="{{ asset('images/logo.png') }}">
```

## Symfony Messenger

It is possible to run Symfony Messenger workers on AWS Lambda.

A dedicated Bref package is available for this: [bref/symfony-messenger](https://github.com/brefphp/symfony-messenger).

## Using cache

As mentioned above the filesystem is readonly, so if you need a persistent cache it must be stored somewhere else (such as Redis, an RDBMS, or DynamoDB).

A Symfony bundle is available for using AWS DynamoDB as cache backend system: [rikudou/psr6-dynamo-db-bundle](https://github.com/RikudouSage/DynamoDbCachePsr6Bundle)

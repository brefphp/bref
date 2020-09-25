---
title: Serverless Symfony applications
current_menu: symfony
introduction: Learn how to deploy serverless Symfony applications on AWS Lambda using Bref.
---

This guide helps you run Symfony applications on AWS Lambda using Bref. These instructions are kept up to date to target the latest Symfony version.

A demo application is available on GitHub at [github.com/mnapoli/bref-symfony-demo](https://github.com/mnapoli/bref-symfony-demo).

## Setup

Assuming your are in existing Symfony project, let's install Bref via Composer:

```
composer require bref/bref
```

Then let's create a `serverless.yml` configuration file (at the root of the project) optimized for Symfony:

```yaml
service: bref-demo-symfony

provider:
    name: aws
    region: us-east-1
    runtime: provided
    environment:
        # Symfony environment variables
        APP_ENV: prod

plugins:
    - ./vendor/bref/bref

package:
    exclude:
        - node_modules/**
        - tests/**

functions:
    website:
        handler: public/index.php
        timeout: 28 # in seconds (API Gateway has a timeout of 29 seconds)
        layers:
            - ${bref:layer.php-74-fpm}
        events:
            -   http: 'ANY /'
            -   http: 'ANY /{proxy+}'
    console:
        handler: bin/console
        timeout: 120 # in seconds
        layers:
            - ${bref:layer.php-74} # PHP
            - ${bref:layer.console} # The "console" layer
```

Now we still have a few modifications to do on the application to make it compatible with AWS Lambda.

Since [the filesystem is readonly](/docs/environment/storage.md) except for `/tmp` we need to customize where the cache and the logs are stored in the `src/Kernel.php` file. This is done by adding 2 new methods to the class:

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

## Using cache

As mentioned above, the filesystem is readonly so if you need persistent cache you need to store it somewhere else.

One great option is using AWS DynamoDB - a fast NoSQL storage. You can install [this bundle](https://github.com/RikudouSage/DynamoDbCachePsr6Bundle)
which integrates into Symfony via composer:

`composer require rikudou/psr6-dynamo-db-bundle`

Then configure the DynamoDB table, create the file `config/packages/dynamo_db_cache.yaml`:

```yaml
rikudou_dynamo_db_cache:
    table: myCacheTableName
```

> Note: This is just a minimal example, see the bundle description for full list of options

And finally one more configuration to replace the cache implementations in production. Create the file
`config/packages/prod/dynamo_db_cache.yaml` (notice the prod in the path name, if you use different environment,
replace `prod` with your environment):

```yaml
rikudou_dynamo_db_cache:
    replace_default_adapter: true
```

This will replace all references to `Symfony\Component\Cache\Adapter\AdapterInterface` and `Symfony\Contracts\Cache\CacheInterface`
with the DynamoDB implementation.

You can then use your cache as usual (for more information see Symfony documentation [here](https://symfony.com/doc/current/cache.html)
and [here](https://symfony.com/doc/current/components/cache.html#basic-usage-psr-6)):

```php
<?php

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MyService
{
    public function __construct(AdapterInterface $cache)
    {
        $item = $cache->getItem('test');
        // do stuff with cache item
        $cache->save($item);
    }
}

class MyService2
{
    public function __construct(CacheInterface $cache)
    {
        $cache->get('test', function (ItemInterface $item) {
            return 'new-value';
        });
    }
}
```

## Deploy

Your application is now ready to be deployed. Follow [the deployment guide](/docs/deploy.md).

## Console

As you may have noticed, we define a function of type "console" in `serverless.yml`. That function is using the [Console runtime](/docs/runtimes/console.md), which lets us run the Symfony Console on AWS Lambda.

To use it follow [the "Console" guide](/docs/runtimes/console.md).

## Logs

While overriding the log's location in the `Kernel` class was necessary for Symfony to run correctly, by default Symfony logs in `stderr`. That is great because Bref [automatically forwards `stderr` to AWS CloudWatch](/docs/environment/logs.md).

However if your application is using Monolog we need to configure Monolog to log into `stderr` as well:

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        # ...
        nested:
            type: stream
            path: "php://stderr"
```

Be aware that Symfony also log deprecations:

```yaml
monolog:
    handlers:
        # ...
        deprecation:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.deprecations.log"
```

Either change the path to `php://stderr` or remove the logging of deprecations entirely.

## Environment variables

Since Symfony 4, the production parameters are configured through environment variables. You can define some in `serverless.yml`.

```yaml
provider:
    environment:
         APP_ENV: prod
```

The secrets (e.g. database passwords) must however not be committed in this file.

To learn more about all this, read the [environment variables documentation](/docs/environment/variables.md).

## Symfony Messenger

It is possible to run Symfony Messenger workers on AWS Lambda.

A dedicated Bref package is available for this: [bref/symfony-messenger](https://github.com/brefphp/symfony-messenger).

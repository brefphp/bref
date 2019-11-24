---
title: Serverless Antidot applications
current_menu: antidot
introduction: Learn how to deploy serverless Antidot Framework applications on AWS Lambda using Bref.
---

This guide helps you run Antidot Framework applications on AWS Lambda using Bref. These instructions are kept up to date to target the latest Antidot Framework version.

A demo application is available on GitHub at [github.com/antidot-framework/antidot-bref-lambda-demo](https://github.com/antidot-framework/antidot-bref-lambda-demo).

## Setup

Assuming your are in existing [Antidot Framework project](https://github.com/antidot-framework/antidot-starter), let's install Bref via Composer:

```
composer require bref/bref
```

Then let's create a `serverless.yml` configuration file (at the root of the project) optimized for Antidot Framework:

```yaml
service: bref-demo-antidot

provider:
    name: aws
    region: us-east-1
    runtime: provided
    environment:
        # Your project environment variables
#        APP_ENV: prod

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
            - ${bref:layer.php-73-fpm}
        events:
            -   http: 'ANY /'
            -   http: 'ANY /{proxy+}'
    console:
        handler: bin/console
        timeout: 120 # in seconds
        layers:
            - ${bref:layer.php-73} # PHP
            - ${bref:layer.console} # The "console" layer
```

Now we still have a few modifications to do on the application to make it compatible with AWS Lambda.

Since [the filesystem is readonly](/docs/environment/storage.html) except for `/tmp` we need to customize where the cache 
is stored in the `config/config.php` and `config/cli-config.php` files. 

```php
<?php
// config/config.php
$cacheConfig = [
    'config_cache_path' => dirname('../tmp/cache') . '/config-cache.php',
];
...
```

```php
<?php
// config/cli-config.php
...
$cacheConfig = [
    'cli_config_cache_path' => dirname('../tmp/cache') . 'cli-config-cache.php',
];
...
```

## Deploy

Your application is now ready to be deployed. Follow [the deployment guide](/docs/deploy.md).

## Console

As you may have noticed, we define a function of type "console" in `serverless.yml`. That function is using the [Console runtime](/docs/runtimes/console.md), which lets us run the Antidot Framework Console on AWS Lambda.

To use it follow [the "Console" guide](/docs/runtimes/console.md).

## Logs

We need to configure Monolog to log into `stderr` as well:

```yaml
# config/services/dependencies.prod.yaml
  monolog:
    handlers:
      default:
        type: 'stream'
        options:
          stream: 'php://stderr'
          level: 400
```

## Environment variables

You can define your environment variables in `serverless.yml` in the [Globals section](https://github.com/awslabs/serverless-application-model/blob/master/docs/globals.rst):

```yaml
Globals:
    Function:
        Environment:
            Variables:
                APP_ENV: prod
```

The secrets (e.g. database passwords) must however not be committed in this file.

To learn more about all this, read the [environment variables documentation](/docs/environment/variables.md).

---
title: Serverless Zend Expressive applications
current_menu: expressive
introduction: Learn how to deploy serverless Zend Expressive applications on AWS Lambda using Bref.
---

This guide helps you run Zend Expressive applications on AWS Lambda using Bref. These instructions are kept up to date to target the latest Expressive version.

A demo application is available on GitHub at [github.com/leonhusmann/expressive-bref](https://github.com/leonhusmann/expressive-bref).

## Setup

Assuming your are in existing Zend Expressive project, let's install Bref via Composer:

```
composer require bref/bref
```

Then let's create a `serverless.yml` configuration file (at the root of the project) optimized for Symfony:

```yaml
service: bref-demo-expressive

provider:
  name: aws
  region: us-east-1
  runtime: provided

plugins:
  - ./vendor/bref/bref

functions:
  api:
    handler: public/index.php
    timeout: 30 # in seconds (API Gateway has a timeout of 30 seconds)
    layers:
      - ${bref:layer.php-73-fpm}
    events:
      -   http: 'ANY /'
      -   http: 'ANY /{proxy+}'
```

Now we still have a few modifications to do on the application to make it compatible with AWS Lambda.

Since [the filesystem is readonly](/docs/environment/storage.md) except for `/tmp` we need to customize where the cache and the logs are stored in the `config/config.php` file. This is done by changing this line:

```diff
diff --git a/config/config.php b/config/config.php
index 66df694..a22f39a 100644
--- a/config/config.php
+++ b/config/config.php
@@ -9,7 +9,7 @@ use Zend\ConfigAggregator\PhpFileProvider;
 // To enable or disable caching, set the `ConfigAggregator::ENABLE_CACHE` boolean in
 // `config/autoload/local.php`.
 $cacheConfig = [
-    'config_cache_path' => 'data/cache/config-cache.php',
+    'config_cache_path' => 'tmp/cache/config-cache.php',
 ];

```

## Deploy

Make sure your not in development mode when deploying.

```shell
$ composer development-disable
```

Your application is now ready to be deployed. Follow [the deployment guide](/docs/deploy.md).

---
title: Serverless Symfony applications
current_menu: symfony
introduction: Learn how to deploy serverless Symfony applications on AWS Lambda using Bref.
---

This guide helps you run Symfony applications on AWS Lambda using Bref. These instructions are kept up-to-date to be compatible with the latest Symfony version.

Multiple demo applications are available on GitHub at [github.com/brefphp/examples/Symfony](https://github.com/brefphp/examples/tree/master/Symfony).

## Setup

First, **follow the [Installation guide](../installation.md)** to create an AWS account and install the necessary tools.

Next, in an existing Symfony project, install Bref and the [Symfony Bridge package](https://github.com/brefphp/symfony-bridge).

```
composer require bref/bref bref/symfony-bridge
```

If you are using [Symfony Flex](https://flex.symfony.com/), it will automatically run
the [bref/symfony-bridge recipe](https://github.com/symfony/recipes-contrib/tree/master/bref/symfony-bridge/0.1) which will perform the following tasks:

- Create a `serverless.yml` configuration file optimized for Symfony.
- Add the `.serverless` folder to the `.gitignore` file.

> Otherwise, you can create the `serverless.yml` file manually at the root of the project. Take a look
at the [default configuration](https://github.com/symfony/recipes-contrib/blob/master/bref/symfony-bridge/0.1/serverless.yaml) provided by the recipe.

You still have a few modifications to do on the application to make it compatible with AWS Lambda.

Since [the filesystem is readonly](/docs/environment/storage.md) except for `/tmp` we need to customize where the cache and logs are stored in
the `src/Kernel.php` file. This is automatically done by the bridge, you just need to use the `BrefKernel` class instead of the default `BaseKernel`:

```diff
// src/Kernel.php

namespace App;

+ use Bref\SymfonyBridge\BrefKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
-use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

- class Kernel extends BaseKernel
+ class Kernel extends BrefKernel
{
    // ...
```

## Deploy

The application is now ready to be deployed. Follow [the deployment guide](/docs/deploy.md).

For better performance in production, warmup the Symfony cache before deploying:

```bash
php bin/console cache:warmup --env=prod
```

## Console

As you may have noticed, we define a function of type "console" in `serverless.yml`. That function is using the [Console runtime](/docs/runtimes/console.md),
which lets us run the Symfony Console on AWS Lambda.

To use it follow [the "Console" guide](/docs/runtimes/console.md).

## Logs

By default, Symfony logs in `stderr`. That is great because Bref [automatically forwards `stderr` to AWS CloudWatch](/docs/environment/logs.md).

However, if the application is using Monolog you need to configure it to log into `stderr` as well:

```yaml
# config/packages/prod/monolog.yaml

monolog:
  handlers:
    # ...
    nested:
      type: stream
      path: php://stderr
```

## Environment variables

Since Symfony 4, the production parameters are configured through environment variables. You can define them in `serverless.yml`.

```yaml
# serverless.yml

provider:
  environment:
    APP_ENV: prod
```

The secrets (e.g. database passwords) must however not be committed in this file.

To learn more about all this, read the [environment variables documentation](/docs/environment/variables.md).

## Trust API Gateway

When hosting your site on Lambda, API Gateway will act as a proxy between the client and your function.

By default, Symfony doesn't trust proxies for security reasons, but it's safe to do it when using API Gateway and Lambda.

This is needed because otherwise, Symfony will not be able to generate URLs properly.

You should add the following lines to `config/packages/framework.yaml`

```yaml
# config/packages/framework.yaml

framework:
  # trust the remote address because API Gateway has no fixed IP or CIDR range that we can target
  trusted_proxies: '127.0.0.1'
  # trust "X-Forwarded-*" headers coming from API Gateway
  trusted_headers: [ 'x-forwarded-for', 'x-forwarded-proto', 'x-forwarded-port' ]
```

Note that API Gateway doesn't set the `X-Forwarded-Host` header, so we don't trust it by default. You should only whitelist this header if you set it manually,
for example in your CloudFront configuration (see how to do it in
the [example Cloudformation template](../websites.md#serving-php-and-static-files-via-cloudfront)).

> Be careful with these settings if your app will not be executed only in a Lambda environment.

You can get more details in the [Symfony documentation](https://symfony.com/doc/current/deployment/proxies.html).

### Getting the user IP

**When using CloudFront** on top of API Gateway, you will not be able to retrieve the client IP address, and you will instead get one of Cloudfront's IP when
calling `Request::getClientIp()`. If you really need this, you will need to
whitelist [every CloudFront IP](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/LocationsOfEdgeServers.html)
in `trusted_proxies`.

## Assets

To deploy Symfony websites, assets needs to be served by AWS S3. Setting up the S3 bucket is already explained in
the [websites documentation](../websites.md#hosting-static-files-with-s3). This section provides additional instructions specific to Symfony assets and Webpack
Encore.

First, you need to [tell Symfony](https://symfony.com/doc/current/reference/configuration/framework.html#base-urls) to use the S3 URL as the assets base URL,
instead of your app domain in production.

```yaml
# config/packages/prod/assets.yaml

framework:
  assets:
    base_urls: 'https://<bucket-name>.s3.amazonaws.com'
```

If using Webpack Encore, you also need to add the following config at the end of `webpack.config.js`

```js
// webpack.config.js

if (Encore.isProduction()) {
    // Note the '/build' at the end of the URL
    Encore.setPublicPath('https://<bucket-name>.s3.amazonaws.com/build');
    Encore.setManifestKeyPrefix('build/')
}
```

Then, you can compile assets for production in the `public` directory, and synchronize that directory to a S3 bucket:

```bash
php bin/console assets:install --env prod
# if using Webpack Encore, additionally run
yarn encore production
aws s3 sync public/ s3://<bucket-name>/ \
  --delete \
  --exclude index.php \
  --exclude public/build/manifest.json \
  --exclude public/build/entrypoint.json
```

Finally, you need to update the `serverless.yml` file to exclude the `assets`, `public/build` and `public/bundles` directories from deployment:

```yaml
# serverless.yml

package:
  patterns:
    - '!assets/**'
    - '!public/build/**'
    - '!public/bundles/**'
    - 'public/build/manifest.json'
    - 'public/build/entrypoint.json'
```

### Assets in templates

For the above configuration to work, assets must be referenced in templates via the `asset()` helper as [recommended by Symfony](https://symfony.com/doc/current/templates.html#linking-to-css-javascript-and-image-assets):

```diff
- <img src="/images/logo.png">
+ <img src="{{ asset('images/logo.png') }}">
```

## Symfony Messenger

It is possible to run Symfony Messenger workers on AWS Lambda.

A dedicated Bref package is available for this: [bref/symfony-messenger](https://github.com/brefphp/symfony-messenger).

## Using cache

As mentioned above the filesystem is readonly, so if you need a persistent cache it must be stored somewhere else (such as Redis, an RDBMS, or DynamoDB).

A Symfony bundle is available for using AWS DynamoDB as cache backend system: [rikudou/psr6-dynamo-db-bundle](https://github.com/RikudouSage/DynamoDbCachePsr6Bundle)

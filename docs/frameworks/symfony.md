---
title: Serverless Symfony applications
currentMenu: symfony
introduction: Learn how to deploy serverless Symfony applications on AWS Lambda using Bref.
---

Here is an example of what your `bref.php` file should contain:

```php
<?php

use App\Kernel;
use Bref\Bridge\Symfony\SymfonyAdapter;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

Debug::enable();

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['APP_ENV'])) {
    (new Dotenv)->load(__DIR__.'/.env');
}
if ($_SERVER['APP_DEBUG'] ?? ('prod' !== ($_SERVER['APP_ENV'] ?? 'dev'))) {
    umask(0000);
}
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? ('prod' !== ($_SERVER['APP_ENV'] ?? 'dev'))));

$app = new \Bref\Application;
$app->httpHandler(new SymfonyAdapter($kernel));
$app->cliHandler(new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel));
$app->run();
```

Since Symfony 4, the production parameters are configured through environment variables. You can define some in `serverless.yml`:

```yaml
functions:
  main:
    ...
    # Symfony configuration using environment variables:
    environment:
      APP_ENV: 'prod'
      APP_DEBUG: '0'
```

The secrets (database passwords, etc.) must however not be committed in this file: define them in the [AWS Console](https://console.aws.amazon.com).

By default, `serverless.yml` contains the list of files and directories to deploy. Make sure to edit that file to include the directories used by Symfony:

```yaml
package:
  exclude:
    - '*'
    - '**'
  include:
    ...
    - 'src/**'
    - 'vendor/**'
    # Below are Symfony-specific files and directories
    - composer.json # Symfony uses it to figure out the root directory
    - 'bin/**'
    - 'config/**'
    - 'var/cache/prod/**' # We want to deploy the production caches
```

If you are using the `symfony/website-skeleton` package you should also include the `translations` directory in `serverless.yml`:

```yaml
package:
  ...
  include:
    ...
    - 'translations/**'
```

The filesystem is readonly on lambdas except for `/tmp`. Because of that you need to customize the path for logs in your `Kernel` class:

```php
public function getLogDir()
{
    // When on the lambda only /tmp is writeable
    if (getenv('LAMBDA_TASK_ROOT') !== false) {
        return '/tmp/log/';
    }

    return $this->getProjectDir().'/var/log';
}
```

The best solution however is not to write log on disks because those are lost. You should use a remote log collector (ELK stack) or a cloud solution like Cloudtrail, Papertrail, Loggly, etc.

We need to build the production cache before deploying. That avoids having the cache regenerated on each HTTP request. Add the following [build hooks](#build-hooks) in `.bref.yml`:

```yaml
hooks:
    build:
        - 'APP_ENV=prod php bin/console cache:clear --no-debug --no-warmup'
        - 'APP_ENV=prod php bin/console cache:warmup'
```

Additionally, even though the cache is pre-warmed during the deploy process, sometimes Twig needs to perform write operations which can cause "Unable to write to cache" exceptions.

The simplest workaround is to disable Twig caching, which allows the config cache to be pre-warmed as normal but prevents Twig from trying to write to a read-only filesystem on the Lambda host.

For example, `config/packages/twig.yaml`

```yaml
twig:
    ...
    cache: false # this can also be set to '/tmp/twig/' if disabling the Twig cache isn't an option for you
```

Alternatively you can set the entire application's cache directory to `/tmp/cache` in the same manner as described for the logs directory in the `Kernel` class. However the caveat is that the pre-compiled config cache won't used in the production environment.

```php
public function getCacheDir()
{
    // When on the lambda only /tmp is writeable
    if (getenv('LAMBDA_TASK_ROOT') !== false) {
        return '/tmp/cache/'.$this->environment;
    }

    return $this->getProjectDir().'/var/cache/'.$this->environment;
}
```

## The `terminate` event

Since PHP is not running in a FastCGI setup, the `terminate` event is run synchronously before the HTTP response is sent back to the client.

If you are not using that event you should not be impacted by this.

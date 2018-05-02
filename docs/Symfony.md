# Deploying Symfony applications

Here is an example of what your `bref.php` file should contain:

```php
<?php

require __DIR__.'/vendor/autoload.php';

$kernel = new \App\Kernel('prod', false);

$app = new \Bref\Application;
$app->httpHandler(new \Bref\Bridge\Symfony\SymfonyAdapter($kernel));
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
build:
    hooks:
        - 'APP_ENV=prod php bin/console cache:clear --no-debug --no-warmup'
        - 'APP_ENV=prod php bin/console cache:warmup'
```

## The `terminate` event

Since PHP is not running in a FastCGI setup, the `terminate` event is run synchronously before the HTTP response is sent back to the client.

If you are not using that event you should not be impacted by this.

---
title: Serverless Symfony applications
currentMenu: symfony
introduction: Learn how to deploy serverless Symfony applications on AWS Lambda using Bref.
---

The first thing to do is tell SAM Local to load the Symfony front controller on each request. Do this in `template.yaml` by pointing the `Handler` directive to `public/index.php`: 

```yaml
Resources:
    MyFunction:
        # ...
        Properties:
            # ...
            Handler: public/index.php
```

Since Symfony 4, the production parameters are configured through environment variables. You can define some in `template.yaml`:

```yaml
Resources:
    MyFunction:
        Type: AWS::Serverless::Function
        Properties:
            # ...
            Environment:
                Variables:
                    APP_ENV: 'prod'
                    APP_DEBUG: '0'
```

The secrets (e.g. database passwords) must however not be committed in this file: define them in the [AWS Console](https://console.aws.amazon.com).

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

The best solution however is to configure Symfony to [not write logs on disk](/docs/environment/logs.md). To send logs to CloudWatch you can configure Monolog to write logs to `stderr`:

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        # ...
        nested:
            type: stream
            path: "php://stderr"
```

You should also set the application's cache directory to `/tmp/cache` in the same manner as described for the logs directory in the `Kernel` class.

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

---
title: Serverless Symfony applications
currentMenu: symfony
introduction: Learn how to deploy serverless Symfony applications on AWS Lambda using Bref.
---

This guide helps you run Symfony applications on AWS Lambda using Bref. These instructions are kept up to date to target the latest Symfony version.

A demo application is available on GitHub at [github.com/mnapoli/bref-symfony-demo](https://github.com/mnapoli/bref-symfony-demo).

## Setup

Assuming your are in existing Symfony project, let's install Bref via Composer:

```
composer require mnapoli/bref
```

Then let's create a `template.yaml` configuration file (at the root of the project) optimized for Symfony:

```yaml
AWSTemplateFormatVersion: '2010-09-09'
Transform: AWS::Serverless-2016-10-31

Globals:
    Function:
        Environment:
            Variables:
                APP_ENV: prod

Resources:
    Website:
        Type: AWS::Serverless::Function
        Properties:
            FunctionName: 'symfony-website'
            CodeUri: .
            Handler: public/index.php
            Timeout: 30 # in seconds (API Gateway has a timeout of 30 seconds)
            MemorySize: 1024
            Runtime: provided
            Layers:
                - 'arn:aws:lambda:us-east-1:209497400698:layer:php-73-fpm:6'
            Events:
                HttpRoot:
                    Type: Api
                    Properties:
                        Path: /
                        Method: ANY
                HttpSubPaths:
                    Type: Api
                    Properties:
                        Path: /{proxy+}
                        Method: ANY

    Console:
        Type: AWS::Serverless::Function
        Properties:
            FunctionName: 'symfony-console'
            CodeUri: .
            Handler: bin/console
            Timeout: 120 # in seconds
            Runtime: provided
            Layers:
                - 'arn:aws:lambda:us-east-1:209497400698:layer:php-73:6' # PHP
                - 'arn:aws:lambda:us-east-1:209497400698:layer:console:6' # The "console" layer

Outputs:
    DemoApi:
        Description: 'URL of our function in the *Prod* environment'
        Value: !Sub 'https://${ServerlessRestApi}.execute-api.${AWS::Region}.amazonaws.com/Prod/'
```

Now we still have a few modifications to do on the application to make it compatible with AWS Lambda.

Since [the filesystem is readonly](/docs/environment/storage.md) except for `/tmp` we need to customize where the cache and the logs are stored in the `src/Kernel.php` file. This is done by adding 2 new methods to the class:

```php
    public function getLogDir()
    {
        // When on the lambda only /tmp is writeable
        if (getenv('LAMBDA_TASK_ROOT') !== false) {
            return '/tmp/log/';
        }

        return $this->getProjectDir().'/var/log';
    }

    public function getCacheDir()
    {
        // When on the lambda only /tmp is writeable
        if (getenv('LAMBDA_TASK_ROOT') !== false) {
            return '/tmp/cache/'.$this->environment;
        }

        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }
```

## Deploy

Your application is now ready to be deployed. Follow [the deployment guide](/docs/deploy.md#deploying-with-sam).

## Console

As you may have noticed, we define a function of type "console" in `template.yaml`. That function is using the [Console runtime](/docs/runtimes/console.md), which lets us run the Symfony Console on AWS Lambda.

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

## Environment variables

Since Symfony 4, the production parameters are configured through environment variables. You can define some in `template.yaml` in the [Globals section](https://github.com/awslabs/serverless-application-model/blob/master/docs/globals.rst):

```yaml
Globals:
    Function:
        Environment:
            Variables:
                APP_ENV: prod
```

The secrets (e.g. database passwords) must however not be committed in this file: define them in the [AWS Console](https://console.aws.amazon.com).

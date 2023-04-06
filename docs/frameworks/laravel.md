---
title: Serverless Laravel applications
current_menu: laravel
introduction: Learn how to deploy serverless Laravel applications on AWS Lambda using Bref.
---

This guide helps you run Laravel applications on AWS Lambda using Bref. These instructions are kept up to date to target the latest Laravel version.

A demo application is available on GitHub at [github.com/brefphp/examples](https://github.com/brefphp/examples).

## Setup

First, make sure you have followed the [Installation guide](../installation.md) to create an AWS account and install the necessary tools.

Next, in an existing Laravel project, install Bref and the [Laravel-Bref package](https://github.com/brefphp/laravel-bridge).

```
composer require bref/bref bref/laravel-bridge --update-with-dependencies
```

Then let's create a [`serverless.yml` configuration file](https://bref.sh/docs/environment/serverless-yml.html):

```
php artisan vendor:publish --tag=serverless-config
```

### How it works

By default, the Laravel-Bref package will automatically configure Laravel to work on AWS Lambda.

If you are curious, the package will automatically:

- enable the `stderr` log driver, to send logs to CloudWatch ([read more about logs](../environment/logs.md))
- enable the [`cookie` session driver](https://laravel.com/docs/session#configuration) (if you prefer, you can configure sessions to be stored in database, DynamoDB or Redis)
- move the storage directory to `/tmp` (because the default storage directory is read-only on Lambda)
- adjust a few more settings ([have a look at the `BrefServiceProvider` for details](https://github.com/brefphp/laravel-bridge/blob/master/src/BrefServiceProvider.php))

## Deployment

We do not want to deploy "dev" caches that were generated on our machine (because paths will be different on AWS Lambda). Let's clear them before deploying:

```bash
php artisan config:clear
```

When running in AWS Lambda, the Laravel application will automatically cache its configuration when booting. You don't need to run `php artisan config:cache` before deploying.

Let's deploy now:

```bash
serverless deploy
```

When finished, the `deploy` command will show the URL of the application.

### Deploying for production

At the moment, we deployed our local installation to Lambda. When deploying for production, we probably don't want to deploy:

- development dependencies,
- our local `.env` file,
- or any other dev artifact.

Follow [the deployment guide](/docs/deploy.md#deploying-for-production) for more details.

## Troubleshooting

In case your application is showing a blank page after being deployed, [have a look at the logs](../environment/logs.md).

## Laravel Artisan

As you may have noticed, we define a function named "artisan" in `serverless.yml`. That function is using the [Console runtime](/docs/runtimes/console.md), which lets us run Laravel Artisan on AWS Lambda.

For example, to execute an `artisan` command on Lambda for the above configuration, run the below command.

```sh
serverless bref:cli --args="<artisan command and options>"
```

For more details follow [the "Console" guide](/docs/runtimes/console.md).

## Assets

To deploy Laravel websites, assets need to be served from AWS S3. The easiest approach is to use the [Server-side website construct of the Lift plugin](https://github.com/getlift/lift/blob/master/docs/server-side-website.md).

This will deploy a Cloudfront distribution that will act as a proxy: it will serve static files directly from S3 and will forward everything else to Lambda. This is very close to how traditional web servers like Apache or Nginx work, which means your application doesn't need to change! For more details, read [the official documentation](https://github.com/getlift/lift/blob/master/docs/server-side-website.md#how-it-works).

First install the plugin:

```bash
serverless plugin install -n serverless-lift
```

Then add this configuration to your `serverless.yml` file:

```yaml
service: laravel
provider:
    # ...

functions:
    # ...

plugins:
    - ./vendor/bref/bref
    - serverless-lift

constructs:
    website:
        type: server-side-website
        assets:
            '/js/*': public/js
            '/css/*': public/css
            '/favicon.ico': public/favicon.ico
            '/robots.txt': public/robots.txt
            # add here any file or directory that needs to be served from S3
```

Before deploying, compile your assets:

```bash
npm run prod
```

Now deploy your website using `serverless deploy`. Lift will create all required resources and take care of
uploading your assets to S3 automatically.

For more details, see the [Websites section](/docs/websites.md) of this documentation and the official [Lift documentation](https://github.com/getlift/lift/blob/master/docs/server-side-website.md).

### Assets in templates

Assets referenced in templates should be via the `asset()` helper:

```html
<script src="{{ asset('js/app.js') }}"></script>
```

If your templates reference some assets via direct path, you should edit them to use the `asset()` helper:

```html
- <img src="/images/logo.png">
+ <img src="{{ asset('images/logo.png') }}">
```

## File storage on S3

Laravel has a [filesystem abstraction](https://laravel.com/docs/filesystem) that lets us easily change where files are stored. When running on Lambda, you will need to use the `s3` adapter to store files on AWS S3.

To do this, set `FILESYSTEM_DISK: s3` either in `serverless.yml` or your production `.env` file. We can also create an S3 bucket via `serverless.yml` directly:

```yaml
# ...
provider:
    # ...
    environment:
        # environment variable for Laravel
        FILESYSTEM_DISK: s3
        AWS_BUCKET: !Ref Storage
    iam:
        role:
            statements:
                # Allow Lambda to read and write files in the S3 buckets
                -   Effect: Allow
                    Action: s3:*
                    Resource:
                        - !Sub '${Storage.Arn}' # the storage bucket
                        - !Sub '${Storage.Arn}/*' # and everything inside

resources:
    Resources:
        # Create our S3 storage bucket using CloudFormation
        Storage:
            Type: AWS::S3::Bucket
```

That's it! The AWS credentials (AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY and AWS_SESSION_TOKEN) are set automatically in AWS Lambda, you don't have to define them.

### Public files

Laravel has a [special disk called `public`](https://laravel.com/docs/filesystem#the-public-disk): this disk stores files that we want to make public, like uploaded photos, generated PDF files, etc.

Again, those files cannot be stored on Lambda, i.e. they cannot be stored in the default `storage/app/public` directory. You need to store those files on S3.

> Do not run `php artisan storage:link` in AWS Lambda: it is now useless, and it will fail because the filesystem is read-only in Lambda.

To store public files on S3, you could replace the disk in the code:

```diff
- Storage::disk('public')->put('avatars/1', $fileContents);
+ Storage::disk('s3')->put('avatars/1', $fileContents);
```

but doing this will not let your application work locally. A better solution, but more complex, involves making the `public` disk configurable. Let's change the configuration in `config/filesystems.php`:

```diff
    /*
    |--------------------------------------------------------------------------
    | Default Public Filesystem Disk
    |--------------------------------------------------------------------------
    */

+   'public' => env('FILESYSTEM_DISK', 'public_local'),

    ...

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

-        'public' => [
+        'public_local' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'token' => env('AWS_SESSION_TOKEN'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

+        's3_public' => [
+            'driver' => 's3',
+            'key' => env('AWS_ACCESS_KEY_ID'),
+            'secret' => env('AWS_SECRET_ACCESS_KEY'),
+            'token' => env('AWS_SESSION_TOKEN'),
+            'region' => env('AWS_DEFAULT_REGION'),
+            'bucket' => env('AWS_PUBLIC_BUCKET'),
+            'url' => env('AWS_URL'),
+        ],

    ],
```

You can now configure the `public` disk to use S3 by changing `serverless.yml` or your production `.env`:

```dotenv
FILESYSTEM_DISK=s3
FILESYSTEM_DISK_PUBLIC=s3
```

## Laravel Queues

To run Laravel Queues on AWS Lambda using [Amazon SQS](https://aws.amazon.com/sqs/), we don't want to run the `php artisan queue:work` command. Instead, we create a function that is invoked immediately when there are new jobs to process.

To create the SQS queue (and the permissions for the Lambda functions to read/write to it), we can either do that manually, or use `serverless.yml`.

To make things simpler, we will use the [Serverless Lift](https://github.com/getlift/lift) plugin to create and configure the SQS queue.

First install the Lift plugin:

```bash
serverless plugin install -n serverless-lift
```

Then use <a href="https://github.com/getlift/lift/blob/master/docs/queue.md">the Queue construct</a> in `serverless.yml`:

```yml
provider:
    # ...
    environment:
        # ...
        QUEUE_CONNECTION: sqs
        SQS_QUEUE: ${construct:jobs.queueUrl}

functions:
    # ...

constructs:
    jobs:
        type: queue
        worker:
            handler: Bref\LaravelBridge\Queue\QueueHandler
            runtime: php-81
            timeout: 60 # seconds
```

We define Laravel environment variables in `provider.environment` (this could also be done in the deployed `.env` file):

- `QUEUE_CONNECTION: sqs` enables the SQS queue connection
- `SQS_QUEUE: ${construct:jobs.queueUrl}` passes the URL of the created SQS queue

If you want to create the SQS queue manually, you will need to set these variables. AWS credentials (`AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY`) are automatically set up with the appropriate permissions for Laravel to use the SQS queue.

That's it! Anytime a job is pushed to Laravel Queues, it will be sent to SQS, and SQS will invoke our "worker" function so that it is processed.

> **Note**:
> 
> In the example above, we set the full SQS queue URL in the `SQS_QUEUE` variable.
> 
If you only set the queue name (which is also valid), you need to set the `SQS_PREFIX` environment variable too. For example: `SQS_PREFIX: "https://sqs.${aws:region}.amazonaws.com/${aws:accountId}"`.

### How it works

When integrated with AWS Lambda, SQS has a built-in retry mechanism and storage for failed messages. These features work slightly differently than Laravel Queues. The "Bref for Laravel" integration does **not** use these SQS features.

Instead, "Bref for Laravel" makes all the feature of Laravel Queues work out of the box, just like on any server. Read more in [the Laravel Queues documentation](https://laravel.com/docs/latest/queues).

> **Note:** the "Bref-Laravel bridge" v1 used to do the opposite. We changed that behavior in Bref v2 in order to make the experience smoother for Laravel users.

## Laravel Octane

To run the HTTP application with [Laravel Octane](https://laravel.com/docs/10.x/octane) instead of PHP-FPM, change the following options in the `web` function:

```yml
functions:
    web:
        handler: Bref\LaravelBridge\Http\OctaneHandler
        runtime: php-81
        environment:
            BREF_LOOP_MAX: 250
        # ...
```

Keep the following details in mind:

- Laravel Octane does not need Swoole or RoadRunner on AWS Lambda, so it is not possible to use Swoole-specific features.
- Octane keeps Laravel booted in a long-running process, [beware of memory leaks](https://laravel.com/docs/10.x/octane#managing-memory-leaks).
- `BREF_LOOP_MAX` specifies the number of HTTP requests handled before the PHP process is restarted (and the memory is cleared).

### Persistent database connections

You can keep database connections persistent across requests to make your application even faster. To do so, set the `OCTANE_PERSIST_DATABASE_SESSIONS` environment variable:

```yml
functions:
    web:
      handler: Bref\LaravelBridge\Http\OctaneHandler
      runtime: php-81
      environment:
          BREF_LOOP_MAX: 250
          OCTANE_PERSIST_DATABASE_SESSIONS: 1
        # ...
```

Note that if you are using PostgreSQL (9.6 or newer), you need to set [`idle_in_transaction_session_timeout`](https://www.postgresql.org/docs/current/runtime-config-client.html#GUC-IDLE-IN-TRANSACTION-SESSION-TIMEOUT) either in your RDS database's parameter group, or on a specific database itself.

```sql
ALTER DATABASE SET idle_in_transaction_session_timeout = '10000' -- 10 seconds in ms
```

## Caching

By default, the Bref bridge will move Laravel's storage and cache directories to `/tmp`. This is because all the filesystem except `/tmp` is read-only.

Note that the `/tmp` directory isn't shared across Lambda instances. If you Lambda function scales up, the cache will be empty in new instances (or after a deployment).

If you want the cache to be shared across all Lambda instances, for example if your application caches a lot of data or if you use it for locking mechanisms (like API rate limiting), you can instead use Redis or DynamoDB.

DynamoDB is the easiest to set up and is "pay per use". Redis is a bit more complex as it requires a VPC and managing instances, but offers slightly faster response times.

### Using DynamoDB

To use DynamoDB as a cache store, change this configuration in `config/cache.php`:

```diff
    # config/cache.php
    'dynamodb' => [
        'driver' => 'dynamodb',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
        'endpoint' => env('DYNAMODB_ENDPOINT'),
+       'attributes' => [
+           'key' => 'id',
+           'expiration' => 'ttl',
+       ]
    ],
```

Then follow [this section of the documentation](/docs/environment/storage.md#deploying-dynamodb-tables) to deploy your DynamoDB table using the Serverless Framework.

## Maintenance mode

Similar to the `php artisan down` command, you may put your app into maintenance mode. All that's required is setting the `MAINTENANCE_MODE` environment variable:

```yml
provider:
    environment:
        MAINTENANCE_MODE: ${param:maintenance, null}
```

You can then deploy:

```bash
# Full deployment (goes through CloudFormation):
serverless deploy --param="maintenance=1"

# Or quick update of the functions config only:
serverless deploy function --function=web --update-config --param="maintenance=1"
serverless deploy function --function=artisan --update-config --param="maintenance=1"
serverless deploy function --function=<function-name> --update-config --param="maintenance=1"
```

To take your app out of maintenance mode, redeploy without the `--param="maintenance=1"` option.

## Laravel Passport

Laravel Passport has a `passport:install` command. However, this command cannot be run in Lambda because it needs to write files to the `storage/` directory.

Instead, here is what you need to do:

- Run `php artisan passport:keys` locally to generate key files.

    This command will generate the `storage/oauth-private.key` and `storage/oauth-public.key` files, which need to be deployed.

    Depending on how you deploy your application (from your machine, or from CI), you may want to whitelist them in `serverless.yml`:

    ```yaml
      package:
          patterns:
              - ...
              # Exclude the 'storage' directory
              - '!storage/**'
              # Except the public and private keys required by Laravel Passport
              - 'storage/oauth-private.key'
              - 'storage/oauth-public.key'
      ```

- You can now deploy the application:

    ```yaml
    serverless deploy
    ```

- Finally, you can create the tokens (which is the second part of the `passport:install` command):

   ```bash
   serverless bref:cli --args="passport:client --personal --name 'Laravel Personal Access Client'"
   serverless bref:cli --args="passport:client --password --name 'Laravel Personal Access Client'"
   ```

All these steps were replacements of running the `passport:install` command [from the Passport documentation](https://laravel.com/docs/passport#installation).

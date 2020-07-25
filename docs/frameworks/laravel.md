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
composer require bref/bref bref/laravel-bridge
```

Then let's create a `serverless.yml` configuration file:

```
php artisan vendor:publish --tag=serverless-config
```

### How it works

By default, the Laravel-Bref package will automatically configure Laravel to work on AWS Lambda

If you are curious, the package will:

- enable the `stderr` log driver, to send logs to CloudWatch ([read more about logs](../environment/logs.md))
- enable the [`cookie` session driver](https://laravel.com/docs/7.x/session#configuration)
    - if you don't need sessions (e.g. for an API), you can manually set `SESSION_DRIVER=array` in `.env`
    - if you prefer, you can configure sessions to be store in database or Redis
- adjust a few more settings ([have a look at the `BrefServiceProvider` for details](https://github.com/brefphp/laravel-bridge/blob/master/src/BrefServiceProvider.php))

## Deployment

We do not want to deploy caches that were generated on our machine (because paths will be different on AWS Lambda). Let's clear them before deploying:

```bash
php artisan config:clear
```

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

```
vendor/bin/bref cli bref-demo-laravel-artisan <bref options> -- <your command, your options>
```

For more details follow [the "Console" guide](/docs/runtimes/console.md).

## Assets

To deploy Laravel websites, we need assets to be served by AWS S3. Setting up the S3 bucket is already explained in the [websites documentation](../websites.md#hosting-static-files-with-s3). This section provides additional instructions specific to Laravel Mix.

First, you can compile assets for production in the `public` directory, then synchronize that directory to a S3 bucket:

```bash
npm run prod
aws s3 sync public/ s3://<bucket-name>/ --delete --exclude index.php
```

Then, the assets need to be included from S3. In the production `.env` file you can now set that variable:

```dotenv
MIX_ASSET_URL=https://<bucket-name>.s3.amazonaws.com
```

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

Laravel has a [filesystem abstraction](https://laravel.com/docs/7.x/filesystem) that lets us easily change where files are stored. When running on Lambda, you will need to use the `s3` adapter to store files on AWS S3. To do this, configure you production `.env` file:

```dotenv
# .env
FILESYSTEM_DRIVER=s3
```

Next, we need to create our bucket via `serverless.yml`:

```yaml
...

provider:
    ...
    environment:
        AWS_BUCKET: # environment variable for Laravel
            Ref: Storage
    iamRoleStatements:
        # Allow Lambda to read and write files in the S3 buckets
        -   Effect: Allow
            Action: s3:*
            Resource:
                - Fn::GetAtt: Storage.Arn # the storage bucket
                - Fn::Join: ['', [Fn::GetAtt: Storage.Arn, '/*']] # everything in the storage bucket

resources:
    Resources:
        Storage:
            Type: AWS::S3::Bucket
```

Because [of a misconfiguration shipped in Laravel](https://github.com/laravel/laravel/pull/5138), the S3 authentication will not work out of the box. You will need to add this line in `config/filesystems.php`:

```diff
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
+           'token' => env('AWS_SESSION_TOKEN'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],
```

That's it! The `'AWS_ACCESS_KEY_ID'`, `'AWS_SECRET_ACCESS_KEY'` and `'AWS_SESSION_TOKEN'` variables are defined automatically on AWS Lambda, you don't have to define them.

### Public files

Laravel has a [special disk called `public`](https://laravel.com/docs/7.x/filesystem#the-public-disk): this disk stores files that we want to make public, like uploaded photos, generated PDF files, etc.

Again, those files cannot be stored on Lambda, i.e. they cannot be stored in the default `storage/app/public` directory. You need to store those files on S3.

> Do not run `php artisan storage:link` on AWS Lambda: it is now useless, and it will fail because the filesystem is read-only on Lambda.

To store public files on S3, you could simply replace the disk in the code:

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

+   'public' => env('FILESYSTEM_DRIVER_PUBLIC', 'public_local'),

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

You can now configure the `public` disk to use S3 by changing your production `.env`:

```dotenv
FILESYSTEM_DRIVER=s3
FILESYSTEM_DRIVER_PUBLIC=s3
```

## Laravel Queues

It is possible to run Laravel Queues on AWS Lambda using [Amazon SQS](https://aws.amazon.com/sqs/).

A dedicated Bref package is available for this: [bref/laravel-bridge](https://github.com/brefphp/laravel-bridge).

## Laravel Passport

Laravel Passport has a `passport:install` command. However, this command cannot be run in Lambda because it needs to write files to the `storage/` directory.

Instead, here is what you need to do:

- Run `php artisan passport:keys` locally to generate key files.

    This command will generate the `storage/oauth-private.key` and `storage/oauth-public.key` files, which need to be deployed.

    Depending on how you deploy your application (from your machine, or from CI), you may want to whitelist them in `serverless.yml`:

    ```yaml
      package:
          exclude:
              ...
          include:
              - storage/oauth-private.key
              - storage/oauth-public.key
      ```

- You can now deploy the application:

    ```yaml
    serverless deploy
    ```

- Finally, you can create the tokens (which is the second part of the `passport:install` command):

   ```bash
   vendor/bin/bref cli <artisan-function-name> -- passport:client --personal --name 'Laravel Personal Access Client'
   vendor/bin/bref cli <artisan-function-name> -- passport:client --password --name 'Laravel Personal Access Client'
   ```

All these steps were replacements of running the `passport:install` command [from the Passport documentation](https://laravel.com/docs/7.x/passport#installation).

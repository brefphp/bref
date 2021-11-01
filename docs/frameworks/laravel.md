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

Then let's create a [`serverless.yml` configuration file](https://github.com/brefphp/laravel-bridge/blob/master/config/serverless.yml):

```
php artisan vendor:publish --tag=serverless-config
```

### How it works

By default, the Laravel-Bref package will automatically configure Laravel to work on AWS Lambda

If you are curious, the package will:

- enable the `stderr` log driver, to send logs to CloudWatch ([read more about logs](../environment/logs.md))
- enable the [`cookie` session driver](https://laravel.com/docs/session#configuration)
    - if you don't need sessions (e.g. for an API), you can manually set `SESSION_DRIVER=array` in `.env`
    - if you prefer, you can configure sessions to be store in database or Redis
- move the cache directory to `/tmp` (because the default storage directory is read-only on Lambda)
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

## Caching

By default, the Bref bridge will move Laravel's cache directory to `/tmp` to avoid issues with the default cache directory that is read-only.

The `/tmp` directory isn't shared across Lambda instances: while this works, this isn't the ideal solution for production workloads.
If you plan on actively using the cache, or anything that uses it (like API rate limiting), you should instead use Redis or DynamoDB.

## Laravel Artisan

As you may have noticed, we define a function named "artisan" in `serverless.yml`. That function is using the [Console runtime](/docs/runtimes/console.md), which lets us run Laravel Artisan on AWS Lambda.

For example, to execute an `artisan` command on Lambda for the above configuration, run the below command.

```
vendor/bin/bref cli bref-demo-laravel-artisan <bref options> -- <your command, your options>
```

For more details follow [the "Console" guide](/docs/runtimes/console.md).

## Assets

To deploy Laravel websites, assets need to be served from AWS S3. The easiest approach is to use the
<a href="https://github.com/getlift/lift/blob/master/docs/server-side-website.md">Server-side website construct of the Lift plugin</a>.

This will deploy a Cloudfront distribution that will act as a proxy: it will serve
static files directly from S3 and will forward everything else to Lambda. This is very close
to how traditional web servers like Apache or Nginx work, which means your application doesn't need to change!
For more details, see <a href="https://github.com/getlift/lift/blob/master/docs/server-side-website.md#how-it-works">the official documentation</a>.

First install the plugin

```bash
serverless plugin install -n serverless-lift
```

Then add this configuration to your `serverless.yml` file.

```yaml
...
service: laravel

provider:
  ...

plugins:
  - ./vendor/bref/bref
  - serverless-lift
    
functions:
  ...

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

Before deploying, compile your assets using Laravel Mix.

```bash
npm run prod
```

Now deploy your website using `serverless deploy`. Lift will create all required resources and take care of
uploading your assets to S3 automatically.

For more details, see the [Websites section](/docs/websites.md) of this documentation
and the official <a href="https://github.com/getlift/lift/blob/master/docs/server-side-website.md">Lift documentation</a>.

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

Laravel has a [filesystem abstraction](https://laravel.com/docs/filesystem) that lets us easily change where files are stored. When running on Lambda, you will need to use the `s3` adapter to store files on AWS S3. To do this, configure you production `.env` file:

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
        # environment variable for Laravel
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

Laravel has a [special disk called `public`](https://laravel.com/docs/filesystem#the-public-disk): this disk stores files that we want to make public, like uploaded photos, generated PDF files, etc.

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
          patterns:
              - 'storage/oauth-private.key'
              - 'storage/oauth-public.key'
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

All these steps were replacements of running the `passport:install` command [from the Passport documentation](https://laravel.com/docs/passport#installation).

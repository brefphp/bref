# Serverless Laravel applications

Deploying Laravel applications requires a few changes for everything to work perfectly.

The below changes have been tested on a Laravel 5.7 version (You may need to adapt them for previous versions).

We assume that you have already installed the serverless and bref dependencies via composer and "init" your project with Bref.

First let's change `bootstrap/app.php` to use the Bref application class:

```diff
- $app = new Illuminate\Foundation\Application(
+ $app = new Bref\Bridge\Laravel\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);
```

This class extends the Laravel application class to set a few Bref-specific helpers. Everything else stays Laravel.

The filesystem is readonly on lambdas except for `/tmp`. Because of that you need to customize the storage path. Add this line in `bootstrap/app.php` after `$app = new Bref\Bridge\Laravel\Application`:

```php
/*
 * Allow overriding the storage path in production using an environment variable.
 */
$app->useStoragePath(env('APP_STORAGE', $app->storagePath()));
```

Then let's write the `bref.php` file in the root folder (not the public folder). This file will now be the entrypoint of the application instead of `public/index.php`. Here is what it should contain:

```php
<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

// Laravel does not create that directory automatically so we have to create it
// You can remove this if you do not use views in your application (e.g. for an API)
if (!is_dir(storage_path('framework/views'))) {
    if (!mkdir(storage_path('framework/views'), 0755, true)) {
        die('Cannot create directory ' . storage_path('framework/views'));
    }
}

$bref = new \Bref\Application;
$bref->httpHandler($app->getBrefHttpAdapter());
$bref->run();
```

We need to define the environment variables in the `serverless.yml` file:

```yaml
functions:
  main:
    ...
    # Laravel configuration using environment variables:
    environment:
      APP_BASE_PATH: '.'
      APP_STORAGE: '/tmp/storage'
      VIEW_COMPILED_PATH: '/tmp/storage/framework/views'
```

Edit `serverless.yml` to include the Laravel folders:

```yaml
package:
  exclude:
    # ...
  include:
    - bref.php
    # Add the following lines:
    - 'app/**'
    - 'bootstrap/**'
    - 'composer.json' # Laravel sometimes uses it to figure out the root directory
    - 'config/**'
    - 'resources/**'
    - 'routes/**'
    - 'vendor/**'
```

We need to build the config cache before deploying. Create the file `.bref.yml` in the root directory of your project and add the following [build hooks](#build-hooks) in it:

```yaml
hooks:
    build:
        # Use the `.env.production` file as `.env`
        - 'rm .env && cp .env.production .env'
        - 'rm bootstrap/cache/*.php'
        - 'php artisan config:cache'
```

Write a `.env.production` file and make sure to set the following variables:

```dotenv
APP_ENV=production
APP_DEBUG=false

# Do not forget to set your app key
APP_KEY=

# We cannot store sessions to disk: if you don't need sessions (API, etc.) use `array`, else store sessions in database
SESSION_DRIVER=array

# Logging to stderr allows the logs to end up in Cloudwatch
LOG_CHANNEL=stderr

# This allows to generate relative file paths for the lambda because when generating the optimized config absolute paths will be everywhere and they will not work because your machine and the lambda environment do not match.
# To avoid that problem, set APP_BASE_PATH variable will allow us to replace the absolute path by `.` (relative path) when generating the lambda.
APP_BASE_PATH=.
APP_STORAGE=/tmp/storage
VIEW_COMPILED_PATH=/tmp/storage/framework/views
```

Since AWS requires a suffix to the URLs (e.g. `/dev` or `/prod`) the default route will not work out of the box. We can change the route for the welcome page:

```php
// routes/web.php

Route::get('/dev', function () {
    return view('welcome');
});
```

However if you setup a custom domain in API Gateway the `/dev` prefix will disappear.

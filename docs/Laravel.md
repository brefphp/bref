# Deploying Laravel applications

Here is an example of what your `bref.php` file should contain:

```php
<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

// Laravel does not create that directory automatically so we have to create it
if (!is_dir(storage_path('framework/views'))) {
    if (!mkdir(storage_path('framework/views'), 0755, true)) {
        die('Cannot create directory ' . storage_path('framework/views'));
    }
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$app = new \Bref\Application;
$app->httpHandler(new Bref\Bridge\Laravel\LaravelAdapter($kernel));
$app->run();
```

When generating the optimized config absolute paths will be everywhere, and they will not work because your machine and the lambda environment do not match. To avoid that problem, change `bootstrap/app.php` as shown below. The `APP_DIR` variable will allow us to replace the absolute path by `.` (relative path) when generating the lambda.

```php
$app = new Illuminate\Foundation\Application(
    env('APP_DIR', realpath(__DIR__.'/../'))
);
```

The filesystem is readonly on lambdas except for `/tmp`. Because of that you need to customize the storage path. Add this line in `bootstrap/app.php` after `$app = new Illuminate\Foundation\Application`:

```php
/*
 * Allow overriding the storage path in production using an environment variable.
 */
$app->useStoragePath(env('APP_STORAGE', $app->storagePath()));
```

Then define the `APP_STORAGE` environment variable in the `serverless.yml` file:

```yaml
functions:
  main:
    ...
    # Laravel configuration using environment variables:
    environment:
      APP_DIR: '.'
      APP_STORAGE: '/tmp/storage'
```

Edit `serverless.yml` to include the Laravel folders:

```yaml
package:
  exclude:
    # ...
  include:
    # ...
    # Add the following lines:
    - 'app/**'
    - 'bootstrap/**'
    - 'config/**'
    - 'resources/**'
    - 'routes/**'
    - 'vendor/**'
```

We need to build the config cache before deploying. Add the following [build hooks](#build-hooks) in `.bref.yml`:

```yaml
hooks:
    build:
        # Use the `.env.production` file as `.env`
        - 'rm .env && cp .env.production .env'
        - 'rm bootstrap/cache/*.php'
        - 'php artisan config:cache'
```

Since we are writing the config cache to disk, all the paths in the config file will be resolved. Since those paths do not exist on our machine (they exist on the lambda environment only) we will have fake paths in the cached config. This is a problem in `config/views.php` because `realpath()` is used. We need to remove the `realpath()` call:

```diff
-    'compiled' => realpath(storage_path('framework/views')),
+    'compiled' => storage_path('framework/views'),
```

Write a `.env.production` file and make sure to set the following variables:

```dotenv
APP_ENV=production
APP_DEBUG=false
# We cannot store sessions to disk: if you don't need sessions (API, etc.) use `array`, else store sessions in database
SESSION_DRIVER=array
# Logging to stderr allows the logs to end up in Cloudwatch
LOG_CHANNEL=stderr
# This allows to generate relative file paths for the lambda
APP_DIR=.
APP_STORAGE=/tmp/storage
```

Since AWS requires a suffix to the URLs (e.g. `/dev` or `/prod`) the default route will not work out of the box. We can change the route for the welcome page:

```php
// routes/web.php

Route::get('/dev', function () {
    return view('welcome');
});
```

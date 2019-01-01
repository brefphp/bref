# Serverless Laravel applications

Deploying Laravel applications requires a few changes for everything to work perfectly.

## Setup

The changes below have been tested for Laravel 5.7, they may need to be adapted for previous versions.

First, install Bref and initialize it:

```
composer require mnapoli/bref
vendor/bin/bref init
```

The filesystem is readonly on lambdas except for `/tmp`. Because of that you need to customize the storage path. Add this line in `bootstrap/app.php` after `$app = new Illuminate\Foundation\Application`:

```php
/*
 * Allow overriding the storage path in production using an environment variable.
 */
$app->useStoragePath(env('APP_STORAGE', $app->storagePath()));
```

We need to define the environment variables in the `template.yaml` file:

TODO: is that still required?

```yaml
Globals:
    Function:
        Environment:
            Variables:
                # Laravel environment variables
                APP_BASE_PATH: '.'
                APP_STORAGE: '/tmp/storage'
                VIEW_COMPILED_PATH: '/tmp/storage/framework/views'
```

> By defining them in the `Globals` section instead of a specific function the variables are applied to *all* functions defined in `template.yaml`.

## Deployment

To deploy the Laravel application we need to perform a few steps:

1. configure `.env` with production configuration
1. generate the cache
1. deploy

### 1. Production configuration

Here is an example of a `.env` file optimized for AWS Lambda. **Read it carefully**:

```dotenv
APP_ENV=production
APP_DEBUG=false

# Do not forget to set your app key
APP_KEY=

# We cannot store sessions to disk: if you don't need sessions (API, etc.)
# then use `array`, else store sessions in database
SESSION_DRIVER=array

# Logging to stderr allows the logs to end up in Cloudwatch
LOG_CHANNEL=stderr

# Because the optimized config is generated outside of AWS Lambda it
# will contain absolute paths that do not work on AWS Lambda.
# To avoid that problem, set APP_BASE_PATH to `.` to force using relative paths.
APP_BASE_PATH=.
APP_STORAGE=/tmp/storage
VIEW_COMPILED_PATH=/tmp/storage/framework/views
```

### 2. Laravel cache

Generate the cache optimized for production by running:

```
php artisan config:cache
```

Do not run this command in your local installation as this cache should not be used in development.

### 3. Deploy

Your application is now ready to be deployed. Follow [the deployment guide](/docs/deploy.md#deploying-with-sam).

## Routing

Because of the [`/Prod/` prefix on API Gateway](/docs/runtimes/http.md#the-prod-prefix) the default route will not work out of the box. We can change the route for the welcome page:

```php
// routes/web.php

Route::get('/dev', function () {
    return view('welcome');
});
```

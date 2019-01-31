<?php

namespace Bref\Bridge\Laravel;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // if we are running in lambda, lets shuffle some things around.
        if (runningInLambda()) {
            $this->setupStorage();
            $this->setupSessionDriver();
            $this->setupLogStack();
        }
        // Allow artisan
        $this->publishes([
            __DIR__ . '/config/cloudformation.yaml' => base_path('template.yaml'),
        ]);

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Since the lambda filesystem is readonly except for
     * `/tmp` we need to customize the storage area.
     */
    public function setupStorage(): void
    {
        $storagePath = '/tmp/storage';

        $storagePaths = [
            "/app/public",
            "/framework/cache/data",
            "/framework/sessions",
            "/framework/testing",
            "/framework/views",
            "/logs"
        ];

        foreach ($storagePaths as $path) {
            mkdir($storagePath . $path, 0777, true);
        }
        $this->app->useStoragePath($storagePath);
    }

    /**
     * Lambda cannot persist sessions to disk.
     */
    public function setupSessionDriver(): void
    {
        // if you try to we will override
        // you and save you from yourself.
        if (env(SESSION_DRIVER) == 'file') {
            # If you need sessions, store them
            # in redis, a database, or cookies
            # anything that scales horizontally
            putenv("SESSION_DRIVER=array");
            Config::set('session.driver', 'array');
        }
    }

    /**
     * At this point, the default single and daily logs will
     * log to `storage_path('logs/laravel.log')` and we have that
     * aimed at /tmp already. But that doesn't do anyone any good.
     * We expect the logs to all go to STDERR so that lambda just
     * automatically logs them to CloudWatch.
     */
    public function setupLogStack(): void
    {
    // If you don't want me messing with this, or you already use stderr, we're done
    if (env('LEAVE_MY_LOGS_ALONE') || Config::get('logging.default') == 'stderr') {
      return;
    }

    // Ok, I will inject stderr into whatever you are doing.
    if (Config::get('logging.default') == 'stack') {
        // Good, you are already using the stack.
        $channels = Config::get('logging.channels.stack.channels');
        if (!in_array('stderr', $channels)) {
            $channels[] = 'stderr';
        }
    } else {
        // Just gonna setup a stack log channel for you here.
        $channels = ['stderr', Config::get('logging.default')];
    }

    Config::set('logging.channels.stack.channels', $channels);
    Config::set('logging.default', 'stack');
    }
}

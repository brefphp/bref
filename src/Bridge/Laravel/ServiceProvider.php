<?php

namespace Bref\Bridge\Laravel;

use Bref\Bridge\Laravel\Console\ConfigSam;
use Bref\Bridge\Laravel\Console\Deploy;
use Bref\Bridge\Laravel\Console\Package;
use Bref\Bridge\Laravel\Console\StartApi;
use Bref\Bridge\Laravel\Console\Update;
use Bref\Bridge\Laravel\Events\SamConfigurationRequested;
use Bref\Bridge\Laravel\Services\ConfigureSam;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * The event => listener mappings for Bref.
     *
     * @var array
     */
    protected $listen = [
        SamConfigurationRequested::class => [
            ConfigureSam::class
        ]
    ];

    /**
     * Bref Console Commands to register.
     * @var array
     */
    protected $commandList = [
        Package::class,
        ConfigSam::class,
        StartApi::class,
        Deploy::class,
        Update::class
    ];

    /**
     * Default path to laravel configuration file in the package
     * @var string
     */
    protected $configPath = __DIR__ . '/config/bref.php';

    /**
     * Default path to the SAM Template in the package
     * @var string
     */
    protected $samTemplatePath = __DIR__ . '/config/cloudformation.yaml';

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load our Laravel Helper functions
        require('helpers.php');

        // if we are running in lambda, lets shuffle some things around.
        if (runningInLambda()) {
            $this->setupStorage();
            $this->setupSessionDriver();
            $this->setupLogStack();
        }
        $this->handlePublishing();

        $this->registerEventListeners();

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

        // Only make the dirs if we have not previously made them
        if (!is_dir($storagePath . end($storagePaths))) {
            reset($storagePaths);
            foreach ($storagePaths as $path) {
                mkdir($storagePath . $path, 0777, true);
            }
        }

        $this->app->useStoragePath($storagePath);
        $this->app['config']['view.compiled'] = realpath(storage_path('framework/views'));
    }

    /**
     * Lambda cannot persist sessions to disk.
     */
    public function setupSessionDriver(): void
    {
        // if you try to we will override
        // you and save you from yourself.
        if (env('SESSION_DRIVER') == 'file') {
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

    /**
     * Publish any artifacts to laravel user space
     */
    public function handlePublishing(): void
    {
        // helps deal with Lumen vs Laravel differences
        if (function_exists('config_path')) {
            $publishConfigPath = config_path('bref.php');
        } else {
            $publishConfigPath = base_path('config/bref.php');
        }

        $this->publishes([$this->configPath => $publishConfigPath], 'bref-configuration');
        $this->publishes([$this->samTemplatePath => base_path('template.yaml')], 'bref-sam-template');
    }

    /**
     * Handle registering any event listeners.
     */
    public function registerEventListeners(): void
    {
        foreach ($this->listens() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    /**
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        return $this->listen;
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if (is_a($this->app, 'Laravel\Lumen\Application')) {
            $this->app->configure('bref');
        }
        $this->mergeConfigFrom($this->configPath, 'bref');
        $this->commands($this->commandList);
    }
}

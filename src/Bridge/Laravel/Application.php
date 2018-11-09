<?php
declare(strict_types=1);

namespace Bref\Bridge\Laravel;

use Illuminate\Contracts\Http\Kernel;

/**
 * Overrides the base Laravel application class for Bref specific behaviors.
 */
class Application extends \Illuminate\Foundation\Application
{
    private $isArtisanConsole = null;

    /**
     * Configure Laravel to run as HTTP handler in Bref
     * and return the correct adapter.
     *
     * Usage example:
     *
     *     $laravel = new \Bref\Bridge\Laravel\Application(...);
     *
     *     $bref = new \Bref\Application;
     *     $bref->httpHandler($laravel->getBrefHttpAdapter());
     *     $bref->run();
     */
    public function getBrefHttpAdapter(): LaravelAdapter
    {
        $this->overrideRunningInConsole(false);

        $kernel = $this->make(Kernel::class);

        return new LaravelAdapter($kernel);
    }

    /**
     * Bref always runs using the `php-cli` binary on AWS lambda.
     *
     * This is a problem because it tricks Laravel into thinking that it
     * runs the Artisan console and not the HTTP application...
     * This can cause issues in some apps or packages.
     *
     * To fix this we override the method to force its return value:
     * - most of the time we don't override the value
     * - when running in Lambda in HTTP we override it to return `false`
     */
    public function runningInConsole()
    {
        // This allows us to override the value to return
        if ($this->isArtisanConsole !== null) {
            return $this->isArtisanConsole;
        }

        // By default we return the result of the parent method
        return parent::runningInConsole();
    }

    public function overrideRunningInConsole(bool $value)
    {
        $this->isArtisanConsole = $value;
    }
}

<?php

namespace Bref\Bridge\Laravel\Events;


use Bref\Bridge\Laravel\Console\Package;

class LambdaPackageRequested
{
    /**
     * Holds a reference to the console command for I/O
     * @var Package
     */
    protected $console;

    /**
     * Console Command Getter
     * @return Package
     */
    public function getConsole(): Package
    {
        return $this->console;
    }

    /**
     * Console command setter
     * @param Package $console
     * @return LambdaPackageRequested
     */
    public function setConsole(Package $console): LambdaPackageRequested
    {
        $this->console = $console;
        return $this;
    }

    /**
     * Console Info Logging
     * @param string $message
     */
    public function info(string $message)
    {
        if ($this->isFromConsole()) {
            $this->console->info($message);
        }
    }

    /**
     * Determine if the event is from a console.
     * @return bool
     */
    public function isFromConsole(): bool
    {
        return isset($this->console);
    }

    /**
     * Console error logging
     * @param string $message
     */
    public function error(string $message)
    {
        if ($this->isFromConsole()) {
            $this->console->error($message);
        }
    }
}

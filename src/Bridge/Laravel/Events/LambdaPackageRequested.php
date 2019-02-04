<?php declare(strict_types=1);

namespace Bref\Bridge\Laravel\Events;

use Bref\Bridge\Laravel\Console\Package;

class LambdaPackageRequested
{
    /**
     * Holds a reference to the console command for I/O
     *
     * @var Package
     */
    protected $console;

    /**
     * Console Command Getter
     */
    public function getConsole(): Package
    {
        return $this->console;
    }

    /**
     * Console command setter
     */
    public function setConsole(Package $console): LambdaPackageRequested
    {
        $this->console = $console;
        return $this;
    }

    /**
     * Console Info Logging
     */
    public function info(string $message): void
    {
        if ($this->isFromConsole()) {
            $this->console->info($message);
        }
    }

    /**
     * Determine if the event is from a console.
     */
    public function isFromConsole(): bool
    {
        return isset($this->console);
    }

    /**
     * Console error logging
     */
    public function error(string $message): void
    {
        if ($this->isFromConsole()) {
            $this->console->error($message);
        }
    }
}

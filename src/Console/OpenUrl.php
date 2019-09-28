<?php declare(strict_types=1);

namespace Bref\Console;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class OpenUrl
{
    public static function open(string $url): void
    {
        $exeFinder = new ExecutableFinder;
        if ($exeFinder->find('open')) {
            // MacOS
            $process = new Process(['open', $url]);
            $process->run();
        } elseif ($exeFinder->find('start')) {
            // Windows
            $process = new Process(['start', $url]);
            $process->run();
        } elseif ($exeFinder->find('xdg-open')) {
            // Linux
            $process = new Process(['xdg-open', $url]);
            $process->run();
        }
    }
}

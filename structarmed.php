<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Core', ['src/Bref.php', 'src/LazySecretsLoader.php'])
    ->layer('Context', 'src/Context/')
    ->layer('Event', 'src/Event/')
    ->layer('Listener', 'src/Listener/')
    ->layer('Runtime', 'src/Runtime/')
    ->layer('ConsoleRuntime', 'src/ConsoleRuntime/')
    ->layer('FpmRuntime', 'src/FpmRuntime/')
    ->layer('FunctionRuntime', 'src/FunctionRuntime/')
    ->ruleset([
        'Context'         => [],
        'Event'           => ['Context'],
        'Listener'        => ['+Event'],
        'Core'            => ['Listener', 'Runtime'],
        'Runtime'         => ['Core', '+Event'],
        'ConsoleRuntime'  => ['Core', 'Context', 'Runtime'],
        'FpmRuntime'      => ['+Runtime'],
        'FunctionRuntime' => ['Core', 'Runtime'],
    ]);

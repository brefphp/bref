<?php declare(strict_types=1);

$extensions = [
    'intl' => class_exists(\Collator::class),
    'apcu' => function_exists('apcu_add'),
    'pdo_pgsql' => extension_loaded('pdo_pgsql'),
];

foreach ($extensions as $extension => $test) {
    if ($test) {
        throw new Exception($extension . ' extension was not supposed to be loaded');
    }

    echo "\033[36m [Disabled] $extension ✓!\033[0m" . PHP_EOL;
}
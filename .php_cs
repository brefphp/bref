<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        '.bref',
        'vendor',
        'tests/Bridge/Symfony/cache',
        'tests/Bridge/Symfony/logs',
    ]);

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@PHP70Migration' => true,
    ])
    ->setFinder($finder);

<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

ini_set('display_errors', '1');

λ(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});

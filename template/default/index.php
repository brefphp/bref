<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

λ(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});

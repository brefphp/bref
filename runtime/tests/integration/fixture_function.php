<?php declare(strict_types=1);

return function ($event, \Bref\Context\Context $context) {
    return [
        'event' => $event,
        'server' => $_SERVER,
        'memory_limit' => ini_get('memory_limit'),
    ];
};
<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

interface SqsEventFactoryInterface
{
    public function createFromPayload(array $payload): SqsEvent;
}

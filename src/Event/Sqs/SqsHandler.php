<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

use Bref\Context\Context;
use Bref\Event\Handler;

/**
 * Handles SQS events.
 */
abstract class SqsHandler implements Handler
{
    private SqsEventFactoryInterface $eventFactory;

    public function __construct(?SqsEventFactoryInterface $eventFactory = null)
    {
        if (! $eventFactory) {
            $eventFactory = new SqsEventFactory;
        }
        $this->eventFactory = $eventFactory;
    }

    /** @var SqsRecord[] */
    private array $failedRecords = [];

    abstract public function handleSqs(SqsEvent $event, Context $context): void;

    /** {@inheritDoc} */
    public function handle($event, Context $context): array
    {
        // Reset the failed records to clear the internal state when using BREF_LOOP_MAX
        $this->failedRecords = [];

        $this->handleSqs($this->eventFactory->createFromPayload($event), $context);

        if (count($this->failedRecords) === 0) {
            return [];
        }

        $failures = array_map(
            function (SqsRecord $record) {
                return ['itemIdentifier' => $record->getMessageId()];
            },
            $this->failedRecords
        );

        return [
            'batchItemFailures' => $failures,
        ];
    }

    final protected function markAsFailed(SqsRecord $record): void
    {
        $this->failedRecords[] = $record;
    }
}

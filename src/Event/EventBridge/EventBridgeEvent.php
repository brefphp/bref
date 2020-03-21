<?php declare(strict_types=1);

namespace Bref\Event\EventBridge;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\LambdaEvent;
use DateTimeImmutable;

/**
 * Represents a Lambda event when Lambda is invoked by EventBridge.
 */
final class EventBridgeEvent implements LambdaEvent
{
    /** @var array */
    private $event;

    /**
     * @param mixed $event
     */
    public function __construct($event)
    {
        if (! is_array($event) || ! isset($event['detail-type'])) {
            throw new InvalidLambdaEvent('EventBridge', $event);
        }
        $this->event = $event;
    }

    public function getId(): string
    {
        return $this->event['id'];
    }

    public function getVersion(): string
    {
        return $this->event['version'];
    }

    public function getAwsRegion(): string
    {
        return $this->event['region'];
    }

    public function getTimestamp(): DateTimeImmutable
    {
        // Date in RFC3339 format per https://docs.aws.amazon.com/eventbridge/latest/APIReference/eventbridge-api.pdf
        return DateTimeImmutable::createFromFormat(DATE_RFC3339, $this->event['time']);
    }

    public function getAwsAccountId(): string
    {
        return $this->event['account'];
    }

    public function getSource(): string
    {
        return $this->event['source'];
    }

    public function getDetailType(): string
    {
        return $this->event['detail-type'];
    }

    /**
     * Returns the content of the EventBridge message.
     *
     * Note that when publishing an event from PHP, we JSON-encode the 'detail' field.
     * However, this method will not return a JSON string: it will return the decoded content.
     * This is how EventBridge works: we publish a message with JSON-encoded data. EventBridge decodes it
     * and triggers listeners with the decoded data.
     *
     * @return mixed
     */
    public function getDetail()
    {
        return $this->event['detail'];
    }

    public function toArray(): array
    {
        return $this->event;
    }
}

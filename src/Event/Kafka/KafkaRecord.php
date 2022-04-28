<?php declare(strict_types = 1);

namespace Bref\Event\Kafka;


use InvalidArgumentException;

final class KafkaRecord
{

    private $record;

    /**
     * @param mixed $record
     */
    public function __construct($record)
    {
        if (! is_array($record)) {
            throw new InvalidArgumentException;
        }
        $this->record = $record;
    }

    public function getTopic(): string
    {
        return $this->record['topic'];
    }

    public function getPartition(): int
    {
        return $this->record['partition'];
    }

    public function getOffset(): int
    {
        return $this->record['offset'];
    }

    public function getTimestamp(): int
    {
        return $this->record['timestamp'];
    }

    public function getValue(): mixed
    {
        return base64_decode($this->record['value']);
    }

    public function getHeaders(): array
    {
        return array_map(
            function (array $chars): string {
                return implode('', array_map(
                    function (int $char): string {
                        return chr($char);
                    },
                    $chars,
                ));
            },
            array_merge(...array_values($this->record['headers']))
        );
    }

}

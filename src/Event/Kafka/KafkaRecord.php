<?php declare(strict_types=1);

namespace Bref\Event\Kafka;

use InvalidArgumentException;

/**
 * @final
 */
class KafkaRecord
{
    private array $record;

    public function __construct(mixed $record)
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

    /**
     * A header in Kafka is an objects, with a single property. The name of the property is the name of the header. Its value is a
     * byte-array, representing a string. We'll normalize it to a hashmap with key and value being strings.
     *
     * @return array<string, string>
     *
     * @see https://kafka.apache.org/25/javadoc/org/apache/kafka/common/header/Headers.html
     */
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

<?php declare(strict_types=1);

namespace Bref\Logs;

use ArrayObject;
use DateTimeInterface;
use JsonSerializable;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;
use Stringable;
use Throwable;

/**
 * Monolog formatter optimized for CloudWatch logs.
 */
class CloudWatchFormatter extends NormalizerFormatter
{
    public function format(LogRecord $record): string
    {
        $level = strtoupper($record->level->name);
        // Make sure everything is kept on one line to count as one record
        $message = str_replace(["\r\n", "\r", "\n"], ' ', $record->message);
        $json = $this->toJson($this->normalizeRecord($record), true);

        return "$level\t$message\t$json\n";
    }

    public function formatBatch(array $records): string
    {
        return implode('', array_map(fn (LogRecord $record) => $this->format($record), $records));
    }

    /**
     * @return array<array|bool|float|int|\stdClass|string|null>
     */
    protected function normalizeRecord(LogRecord $record): array
    {
        $data = [
            'message' => $record->message,
            'level' => strtoupper($record->level->name),
        ];
        $context = $record->context;
        // Move any exception to the root
        $exception = $context['exception'] ?? null;
        if ($exception instanceof Throwable) {
            $data['exception'] = $exception;
            unset($context['exception']);
        }
        if ($context !== []) {
            $data['context'] = $context;
        }
        if ($record->extra !== []) {
            $data['extra'] = $record->extra;
        }

        return $this->normalize($data);
    }

    /**
     * @return null|scalar|array<array|scalar|object|null>|object
     */
    protected function normalize(mixed $data, int $depth = 0): mixed
    {
        if ($depth > $this->maxNormalizeDepth) {
            return 'Over ' . $this->maxNormalizeDepth . ' levels deep, aborting normalization';
        }

        if (is_array($data)) {
            $normalized = [];

            $count = 1;
            foreach ($data as $key => $value) {
                if ($count++ > $this->maxNormalizeItemCount) {
                    $normalized['...'] = 'Over ' . $this->maxNormalizeItemCount . ' items (' . count($data) . ' total), aborting normalization';
                    break;
                }

                $normalized[$key] = $this->normalize($value, $depth + 1);
            }

            return $normalized;
        }

        if (is_object($data)) {
            if ($data instanceof DateTimeInterface) {
                return $this->formatDate($data);
            }

            if ($data instanceof Throwable) {
                return $this->normalizeException($data, $depth);
            }

            // if the object has specific json serializability we want to make sure we skip the __toString treatment below
            if ($data instanceof JsonSerializable) {
                return $data;
            }

            if ($data instanceof Stringable) {
                return $data->__toString();
            }

            if (get_class($data) === '__PHP_Incomplete_Class') {
                return new ArrayObject($data);
            }

            return $data;
        }

        if (is_resource($data)) {
            return parent::normalize($data);
        }

        return $data;
    }
}

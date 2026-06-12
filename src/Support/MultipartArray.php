<?php declare(strict_types=1);

namespace Bref\Support;

/**
 * Build nested arrays from multipart form field names with bracket notation.
 *
 * Adapted from Laravel Vapor's {@see https://github.com/laravel/vapor-core/blob/2.0/src/Arr.php Arr::setMultipartArrayValue}.
 */
final class MultipartArray
{
    /**
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    public static function setValue(array $array, string $name, mixed $value): array
    {
        if (! str_contains($name, '[')) {
            if (array_key_exists($name, $array) && ! is_array($array[$name])) {
                $array[$name] = [$array[$name], $value];
            } else {
                $array[$name] = $value;
            }

            return $array;
        }

        $existingValue = self::getValueAtPath($array, $name);
        if ($existingValue !== null && ! is_array($existingValue)) {
            return self::appendDuplicateFieldValue($array, $name, $value);
        }

        return self::setMultipartArrayValue($array, $name, $value);
    }

    /**
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    private static function setMultipartArrayValue(array $array, string $name, mixed $value): array
    {
        $segments = explode('[', $name);

        $pointer = &$array;

        foreach ($segments as $key => $segment) {
            if ($key === 0) {
                $pointer = &$pointer[$segment];

                continue;
            }

            if (self::malformedMultipartSegment($segment)) {
                $array[$name] = $value;

                return $array;
            }

            $segment = substr($segment, 0, -1);

            if ($segment === '') {
                $pointer = &$pointer[];
            } else {
                $pointer = &$pointer[$segment];
            }
        }

        $pointer = $value;

        return $array;
    }

    private static function malformedMultipartSegment(string $segment): bool
    {
        return $segment === '' || substr($segment, -1) !== ']';
    }

    /**
     * When the same field name is submitted more than once, append the new value
     * to the parent list instead of overwriting the existing entry.
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    private static function appendDuplicateFieldValue(array $array, string $name, mixed $value): array
    {
        $segments = explode('[', $name);
        $parent = &$array[$segments[0]];

        for ($i = 1; $i < count($segments) - 1; $i++) {
            $segment = substr($segments[$i], 0, -1);
            $parent = &$parent[$segment];
        }

        $parent[] = $value;

        return $array;
    }

    /**
     * @param array<string, mixed> $array
     */
    private static function getValueAtPath(array $array, string $name): mixed
    {
        if (! str_contains($name, '[')) {
            return $array[$name] ?? null;
        }

        $segments = explode('[', $name);
        $pointer = $array;

        foreach ($segments as $key => $segment) {
            if ($key === 0) {
                if (! is_array($pointer) || ! array_key_exists($segment, $pointer)) {
                    return null;
                }
                $pointer = $pointer[$segment];

                continue;
            }

            if (self::malformedMultipartSegment($segment)) {
                return null;
            }

            $segment = substr($segment, 0, -1);

            if ($segment === '') {
                if (! is_array($pointer)) {
                    return null;
                }
                $pointer = end($pointer);
                if ($pointer === false) {
                    return null;
                }
            } else {
                if (! is_array($pointer) || ! array_key_exists($segment, $pointer)) {
                    return null;
                }
                $pointer = $pointer[$segment];
            }
        }

        return $pointer;
    }
}

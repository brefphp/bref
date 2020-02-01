<?php declare(strict_types=1);

namespace Bref\Event\S3;

/**
 * Describes the S3 object (or file) that triggered the Lambda event.
 */
final class BucketObject
{
    /** @var string */
    private $key;
    /** @var int */
    private $size;
    /** @var string|null */
    private $versionId;

    /**
     * @internal
     */
    public function __construct(string $key, int $size, ?string $versionId = null)
    {
        $this->key = $key;
        $this->size = $size;
        $this->versionId = $versionId;
    }

    /**
     * @return string A S3 key is similar to a file path.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return int Object/file size.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    public function getVersionId(): ?string
    {
        return $this->versionId;
    }
}

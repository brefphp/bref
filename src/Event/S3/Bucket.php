<?php declare(strict_types=1);

namespace Bref\Event\S3;

/**
 * Describes the bucket that triggered the Lambda event.
 */
final class Bucket
{
    /** @var string */
    private $name;
    /** @var string */
    private $arn;

    /**
     * @internal
     */
    public function __construct(string $name, string $arn)
    {
        $this->name = $name;
        $this->arn = $arn;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArn(): string
    {
        return $this->arn;
    }
}

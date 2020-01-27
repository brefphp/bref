<?php declare(strict_types=1);

namespace Bref\Event\Sns;

/**
 * SNS message attribute.
 *
 * @see https://docs.aws.amazon.com/sns/latest/api/API_MessageAttributeValue.html
 * @see https://github.com/aws/aws-lambda-java-libs/blob/master/aws-lambda-java-events/src/main/java/com/amazonaws/services/lambda/runtime/events/SNSEvent.java
 */
class MessageAttribute
{
    /** @var array */
    private $attribute;

    public function __construct(array $attribute)
    {
        $this->attribute = $attribute;
    }

    public function getType(): string
    {
        return $this->attribute['Type'];
    }

    public function getValue(): string
    {
        return $this->attribute['Value'];
    }

    /**
     * Returns the attribute original data as an array.
     *
     * Use this method if you want to access data that is not returned by a method in this class.
     */
    public function toArray(): array
    {
        return $this->attribute;
    }
}

<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;

/**
 * A handler to add support to SqsHandler.
 */
class SqsWrapper implements BrefHandler
{
    /** @var SqsHandler */
    private $callable;

    public function __construct(SqsHandler $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($event, Context $context)
    {
        return $this->callable->__invoke(new SqsEvent($event), $context);
    }
}

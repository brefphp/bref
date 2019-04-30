<?php declare(strict_types=1);

namespace Bref\Test\Context;

use Bref\Context\ContextBuilder;
use PHPUnit\Framework\TestCase;

class ContextBuilderTest extends TestCase
{
    public function test basic build()
    {
        $expectedAwsRequestId = 'test-request-id';
        $expectedInvokedFunctionArn = 'test-invocation-arn';
        $expectedTraceId = 'test-trace-id';

        $contextBuilder = new ContextBuilder;
        $contextBuilder->setAwsRequestId($expectedAwsRequestId);
        $contextBuilder->setInvokedFunctionArn($expectedInvokedFunctionArn);
        $contextBuilder->setTraceId($expectedTraceId);

        $context = $contextBuilder->buildContext();

        $this->assertEquals($expectedAwsRequestId, $context->getAwsRequestId());
        $this->assertEquals($expectedInvokedFunctionArn, $context->getInvokedFunctionArn());
        $this->assertEquals($expectedTraceId, $context->getTraceId());
    }

    public function test can build empty context()
    {
        $contextBuilder = new ContextBuilder;

        $context = $contextBuilder->buildContext();

        $this->assertEquals('', $context->getAwsRequestId());
        $this->assertLessThan(0, $context->getRemainingTimeInMillis());
        $this->assertEquals('', $context->getInvokedFunctionArn());
        $this->assertEquals('', $context->getTraceId());
    }
}

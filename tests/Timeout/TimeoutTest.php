<?php declare(strict_types=1);

namespace Timeout;

use Bref\Timeout\LambdaTimeout;
use Bref\Timeout\Timeout;
use PHPUnit\Framework\TestCase;

class TimeoutTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (! function_exists('pcntl_async_signals')) {
            self::markTestSkipped('PCNTL extension is not enabled.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        unset($_SERVER['LAMBDA_INVOCATION_CONTEXT']);
        unset($_SERVER['BREF_TIMEOUT']);
    }

    protected function tearDown(): void
    {
        Timeout::reset();
        parent::tearDown();
    }

    public function testEnableWithoutContext()
    {
        $this->expectException(\LogicException::class);
        Timeout::enable();
    }

    public function testEnableWithBrefTimeoutInactive()
    {
        $_SERVER['BREF_TIMEOUT'] = -1;
        $_SERVER['LAMBDA_INVOCATION_CONTEXT'] = json_encode(['deadlineMs' => (time() + 30) * 1000]);

        Timeout::enable();
        $timeout = pcntl_alarm(0);
        $this->assertSame(0, $timeout, 'Timeout should not be active when BREF_TIMEOUT=-1');
    }

    public function testEnableWithBrefTimeout()
    {
        $_SERVER['BREF_TIMEOUT'] = 10;

        Timeout::enable();
        $timeout = pcntl_alarm(0);
        $this->assertSame(10, $timeout, 'BREF_TIMEOUT=10 should have effect');
    }

    public function testEnableWithBrefTimeoutAndContext()
    {
        $_SERVER['BREF_TIMEOUT'] = 10;
        $_SERVER['LAMBDA_INVOCATION_CONTEXT'] = json_encode(['deadlineMs' => (time() + 30) * 1000]);

        Timeout::enable();
        $timeout = pcntl_alarm(0);
        $this->assertSame(10, $timeout, 'BREF_TIMEOUT=10 should have effect over context');
    }

    public function testEnableWithBrefTimeoutZeroAndContext()
    {
        $_SERVER['BREF_TIMEOUT'] = 0;
        $_SERVER['LAMBDA_INVOCATION_CONTEXT'] = json_encode(['deadlineMs' => (time() + 30) * 1000]);

        Timeout::enable();
        $timeout = pcntl_alarm(0);
        $this->assertEqualsWithDelta(30, $timeout, 1, 'BREF_TIMEOUT=0 should fallback to context');
    }

    public function testEnableWithContext()
    {
        $_SERVER['LAMBDA_INVOCATION_CONTEXT'] = json_encode(['deadlineMs' => (time() + 30) * 1000]);

        Timeout::enable();
        $timeout = pcntl_alarm(0);
        $this->assertEqualsWithDelta(30, $timeout, 1);
    }

    public function testTimeoutAfter()
    {
        $start = microtime(true);
        Timeout::timeoutAfter(2);
        try {
            sleep(4);
            $this->fail('We expect a LambdaTimeout before we reach this line');
        } catch (LambdaTimeout $e) {
            $time = 1000 * (microtime(true) - $start);
            $this->assertEqualsWithDelta(2000, $time, 200, 'We must wait about 2 seconds');
        } catch (\Throwable $e) {
            $this->fail('It must throw a LambdaTimeout.');
        }
    }
}

<?php declare(strict_types=1);

namespace Bref\Test\Runtime;

use Bref\Runtime\ColdStartTracker;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ColdStartTrackerTest extends TestCase
{
    private const COLD_START_FILE = '/tmp/.bref-cold-start';

    protected function setUp(): void
    {
        $this->resetColdStartTracker();
    }

    protected function tearDown(): void
    {
        $this->resetColdStartTracker();
    }

    public function test_first_invocation_after_initialization_is_a_user_facing_cold_start(): void
    {
        ColdStartTracker::init();
        ColdStartTracker::coldStartFinished();
        ColdStartTracker::invocationStarted();

        $this->assertTrue(ColdStartTracker::currentInvocationIsColdStart());
        $this->assertTrue(ColdStartTracker::currentInvocationIsUserFacingColdStart());
        $this->assertFalse(ColdStartTracker::wasProactiveInitialization());
    }

    public function test_short_idle_time_after_initialization_is_not_a_proactive_initialization(): void
    {
        ColdStartTracker::init();
        ColdStartTracker::coldStartFinished();

        ColdStartTracker::invocationStarted();

        $this->assertTrue(ColdStartTracker::currentInvocationIsUserFacingColdStart());
        $this->assertFalse(ColdStartTracker::wasProactiveInitialization());
    }

    public function test_first_invocation_more_than_fifty_milliseconds_after_initialization_is_proactive(): void
    {
        ColdStartTracker::init();
        ColdStartTracker::coldStartFinished();

        usleep(75_000);

        ColdStartTracker::invocationStarted();

        $this->assertTrue(ColdStartTracker::currentInvocationIsColdStart());
        $this->assertFalse(ColdStartTracker::currentInvocationIsUserFacingColdStart());
        $this->assertTrue(ColdStartTracker::wasProactiveInitialization());
    }

    public function test_second_invocation_after_user_facing_cold_start_is_warm(): void
    {
        ColdStartTracker::init();
        ColdStartTracker::coldStartFinished();

        ColdStartTracker::invocationStarted();
        ColdStartTracker::invocationStarted();

        $this->assertFalse(ColdStartTracker::currentInvocationIsColdStart());
        $this->assertFalse(ColdStartTracker::currentInvocationIsUserFacingColdStart());
        $this->assertFalse(ColdStartTracker::wasProactiveInitialization());
    }

    public function test_proactive_initialization_annotation_does_not_leak_to_warm_invocations(): void
    {
        ColdStartTracker::init();
        ColdStartTracker::coldStartFinished();
        $this->setColdStartTrackerProperty('coldStartEndedTime', microtime(true) - 0.075);

        ColdStartTracker::invocationStarted();

        $this->assertTrue(ColdStartTracker::currentInvocationIsColdStart());
        $this->assertFalse(ColdStartTracker::currentInvocationIsUserFacingColdStart());
        $this->assertTrue(ColdStartTracker::wasProactiveInitialization());

        ColdStartTracker::invocationStarted();

        $this->assertFalse(ColdStartTracker::currentInvocationIsColdStart());
        $this->assertFalse(ColdStartTracker::currentInvocationIsUserFacingColdStart());
        $this->assertFalse(ColdStartTracker::wasProactiveInitialization());
    }

    public function test_process_restart_inside_same_sandbox_is_a_warm_start(): void
    {
        touch(self::COLD_START_FILE);

        ColdStartTracker::init();
        ColdStartTracker::coldStartFinished();
        ColdStartTracker::invocationStarted();

        $this->assertFalse(ColdStartTracker::currentInvocationIsColdStart());
        $this->assertFalse(ColdStartTracker::currentInvocationIsUserFacingColdStart());
        $this->assertFalse(ColdStartTracker::wasProactiveInitialization());
    }

    public function test_records_cold_start_timestamps(): void
    {
        $beforeInit = microtime(true);
        ColdStartTracker::init();
        $afterInit = microtime(true);

        $beforeFinished = microtime(true);
        ColdStartTracker::coldStartFinished();
        $afterFinished = microtime(true);

        $this->assertGreaterThanOrEqual($beforeInit, ColdStartTracker::getColdStartBeginningTime());
        $this->assertLessThanOrEqual($afterInit, ColdStartTracker::getColdStartBeginningTime());
        $this->assertGreaterThanOrEqual($beforeFinished, ColdStartTracker::getColdStartEndedTime());
        $this->assertLessThanOrEqual($afterFinished, ColdStartTracker::getColdStartEndedTime());
    }

    private function resetColdStartTracker(): void
    {
        if (file_exists(self::COLD_START_FILE)) {
            unlink(self::COLD_START_FILE);
        }

        $this->setColdStartTrackerProperty('currentInvocationIsColdStart', false);
        $this->setColdStartTrackerProperty('coldStartBeginningTime', null);
        $this->setColdStartTrackerProperty('coldStartEndedTime', null);
        $this->setColdStartTrackerProperty('hasFirstInvocationStarted', false);
        $this->setColdStartTrackerProperty('wasProactiveInitialization', false);
    }

    private function setColdStartTrackerProperty(string $property, mixed $value): void
    {
        $reflection = new ReflectionProperty(ColdStartTracker::class, $property);
        $reflection->setValue(null, $value);
    }
}

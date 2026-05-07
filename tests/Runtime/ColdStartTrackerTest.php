<?php declare(strict_types=1);

namespace Bref\Test\Runtime;

use Bref\Runtime\ColdStartTracker;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ColdStartTrackerTest extends TestCase
{
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

    public function test_proactive_initialization_annotation_does_not_leak_to_warm_invocations(): void
    {
        ColdStartTracker::init();
        ColdStartTracker::coldStartFinished();
        $this->setColdStartTrackerProperty('coldStartEndedTime', microtime(true) - 0.2);

        ColdStartTracker::invocationStarted();
        ColdStartTracker::invocationStarted();

        $this->assertFalse(ColdStartTracker::currentInvocationIsColdStart());
        $this->assertFalse(ColdStartTracker::currentInvocationIsUserFacingColdStart());
        $this->assertFalse(ColdStartTracker::wasProactiveInitialization());
    }

    private function resetColdStartTracker(): void
    {
        if (file_exists('/tmp/.bref-cold-start')) {
            unlink('/tmp/.bref-cold-start');
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

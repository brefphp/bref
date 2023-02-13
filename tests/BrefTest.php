<?php declare(strict_types=1);

namespace Bref\Test;

use Bref\Bref;
use Bref\Runtime\FileHandlerLocator;
use PHPUnit\Framework\TestCase;

class BrefTest extends TestCase
{
    protected function setUp(): void
    {
        Bref::reset();
    }

    public function test default container(): void
    {
        $_SERVER['LAMBDA_TASK_ROOT'] = getcwd();

        $this->assertInstanceOf(FileHandlerLocator::class, Bref::getContainer());
    }

    public function test override the container(): void
    {
        $container = new FileHandlerLocator('');

        Bref::setContainer(function () use ($container) {
            return $container;
        });

        $this->assertSame($container, Bref::getContainer());
    }

    public function test hooks(): void
    {
        $beforeStartup1 = false;
        $beforeStartup2 = false;
        $beforeInvoke = false;

        // Check that we can set multiple handlers
        Bref::beforeStartup(function () use (&$beforeStartup1) {
            return $beforeStartup1 = true;
        });
        Bref::beforeStartup(function () use (&$beforeStartup2) {
            return $beforeStartup2 = true;
        });
        Bref::beforeInvoke(function () use (&$beforeInvoke) {
            return $beforeInvoke = true;
        });

        $this->assertFalse($beforeStartup1);
        $this->assertFalse($beforeStartup2);
        $this->assertFalse($beforeInvoke);

        Bref::triggerHooks('beforeStartup');
        $this->assertTrue($beforeStartup1);
        $this->assertTrue($beforeStartup2);
        $this->assertFalse($beforeInvoke);

        Bref::triggerHooks('beforeInvoke');
        $this->assertTrue($beforeInvoke);
    }
}

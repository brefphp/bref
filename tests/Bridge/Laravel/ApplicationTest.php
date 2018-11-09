<?php
declare(strict_types=1);

namespace Bref\Test\Bridge\Laravel;

use Bref\Bridge\Laravel\Application;
use Illuminate\Contracts\Http\Kernel;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class ApplicationTest extends TestCase
{
    public function test runningInConsole returns the parent result by default()
    {
        $defaultLaravelApp = new \Illuminate\Foundation\Application(__DIR__);
        $brefLaravelApp = new Application(__DIR__);

        self::assertSame($defaultLaravelApp->runningInConsole(), $brefLaravelApp->runningInConsole());
    }

    public function test runningInConsole can be overridden()
    {
        $app = new Application(__DIR__);

        $app->overrideRunningInConsole(true);
        self::assertTrue($app->runningInConsole());

        $app->overrideRunningInConsole(false);
        self::assertFalse($app->runningInConsole());
    }

    public function test can return an http adapter()
    {
        $app = new Application(__DIR__);
        $app->singleton(Kernel::class, \Illuminate\Foundation\Http\Kernel::class);

        $adapter = $app->getBrefHttpAdapter();

        self::assertInstanceOf(RequestHandlerInterface::class, $adapter);
    }

    public function test is not in console when running http()
    {
        $app = new Application(__DIR__);
        $app->singleton(Kernel::class, \Illuminate\Foundation\Http\Kernel::class);

        $app->getBrefHttpAdapter();

        self::assertFalse($app->runningInConsole());
    }
}

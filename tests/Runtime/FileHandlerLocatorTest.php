<?php declare(strict_types=1);

namespace Bref\Test\Runtime;

use Bref\Runtime\FileHandlerLocator;
use Bref\Runtime\HandlerNotFound;
use Closure;
use PHPUnit\Framework\TestCase;

class FileHandlerLocatorTest extends TestCase
{
    public function test provides()
    {
        $locator = new FileHandlerLocator(__DIR__ . '/Fixtures');

        $this->assertTrue($locator->has('foo.php'));
        $this->assertFalse($locator->has('bar.php'));

        $this->assertInstanceOf(Closure::class, $locator->get('foo.php'));
    }

    public function test expects file to return handler()
    {
        $locator = new FileHandlerLocator(__DIR__ . '/Fixtures');

        $this->expectException(HandlerNotFound::class);

        $locator->get('broken.php');
    }

    public function test accepts empty directory()
    {
        $locator = new FileHandlerLocator;

        $this->assertTrue($locator->has('tests/BrefTest.php'));
    }
}

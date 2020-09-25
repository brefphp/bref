<?php declare(strict_types=1);

namespace Bref\Test;

use Bref\Bref;
use Bref\Runtime\FileHandlerLocator;
use PHPUnit\Framework\TestCase;

class BrefTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
}

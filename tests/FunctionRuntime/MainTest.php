<?php declare(strict_types=1);

namespace Bref\Test\FunctionRuntime;

use Bref\FunctionRuntime\Main;
use Bref\Test\RuntimeTestCase;

class MainTest extends RuntimeTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $_SERVER['LAMBDA_TASK_ROOT'] = __DIR__ . '/../Runtime/Fixtures';
        putenv('_HANDLER=foo.php');
    }

    public function tearDown(): void
    {
        unset($_SERVER['LAMBDA_TASK_ROOT']);
        putenv('_HANDLER');
        putenv('BREF_LOOP_MAX');
        ini_restore('opcache.file_update_protection');

        parent::tearDown();
    }

    public function test BREF_LOOP_MAX disables OPcache file update protection()
    {
        putenv('BREF_LOOP_MAX=100');

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $this->assertSame('0', ini_get('opcache.file_update_protection'));
    }

    public function test OPcache settings are unchanged without BREF_LOOP_MAX()
    {
        $fileUpdateProtection = ini_get('opcache.file_update_protection');

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $this->assertSame($fileUpdateProtection, ini_get('opcache.file_update_protection'));
    }
}

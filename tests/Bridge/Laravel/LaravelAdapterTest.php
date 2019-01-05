<?php declare(strict_types=1);

namespace Bref\Test\Bridge\Laravel;

use Bref\Bridge\Laravel\LaravelAdapter;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Diactoros\ServerRequest;

class LaravelAdapterTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $fs = new Filesystem;
        $fs->remove(__DIR__ . '/bootstrap/cache/*.php');
        $fs->remove(__DIR__ . '/storage/logs/laravel.log');
    }

    public function test Laravel HTTP applications are adapted()
    {
        $app = $this->createLaravel();

        /** @var Router $router */
        $router = $app->get('router');
        $router->get('/', function () {
            return new Response('Hello world!');
        });

        $adapter = new LaravelAdapter($app->make(Kernel::class));
        $response = $adapter->handle(new ServerRequest([], [], '/'));

        self::assertSame('Hello world!', (string) $response->getBody());
    }

    public function test HTTP headers are forwarded()
    {
        $app = $this->createLaravel();

        /** @var Router $router */
        $router = $app->get('router');
        $router->get('/', function (Request $request) {
            return new Response('Hello ' . $request->header('X-HELLO'));
        });

        $adapter = new LaravelAdapter($app->make(Kernel::class));
        $request = new ServerRequest([], [], '/');
        $request = $request->withHeader('X-HELLO', 'world!');
        $response = $adapter->handle($request);

        self::assertSame('Hello world!', (string) $response->getBody());
    }

    private function createLaravel(): Application
    {
        $app = new Application(__DIR__);
        $app->singleton(Kernel::class, \Illuminate\Foundation\Http\Kernel::class);

        return $app;
    }
}

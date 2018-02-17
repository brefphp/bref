<?php
declare(strict_types=1);

namespace Bref\Test\Bridge\Symfony;

use Bref\Bridge\Symfony\SymfonyAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Zend\Diactoros\ServerRequest;

class SymfonyAdapterTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $fs = new Filesystem;
        $fs->remove([__DIR__ . '/cache', __DIR__ . '/logs']);
    }

    public function test Symfony applications are adapted()
    {
        $adapter = new SymfonyAdapter($this->createKernel());
        $response = $adapter->handle(new ServerRequest([], [], '/foo'));

        self::assertSame('Hello world!', (string) $response->getBody());
    }

    public function test 404 are PSR7 responses and not exceptions()
    {
        $adapter = new SymfonyAdapter($this->createKernel());
        $response = $adapter->handle(new ServerRequest([], [], '/bar'));

        self::assertSame(404, $response->getStatusCode());
    }

    private function createKernel() : HttpKernelInterface
    {
        return new class('dev', false) extends Kernel implements EventSubscriberInterface {
            use MicroKernelTrait;

            public function registerBundles()
            {
                return [new FrameworkBundle];
            }

            protected function configureContainer(ContainerBuilder $c)
            {
                $c->loadFromExtension('framework', [
                    'secret' => 'foo',
                ]);
            }

            protected function configureRoutes(RouteCollectionBuilder $routes)
            {
                $routes->add('/foo', 'kernel:testAction');
            }

            public function testAction()
            {
                return new Response('Hello world!');
            }

            public static function getSubscribedEvents()
            {
                return [KernelEvents::EXCEPTION => 'onKernelException'];
            }

            /**
             * We have to handle NotFound exceptions ourselves because they are not handled by the micro-kernel
             */
            public function onKernelException(GetResponseForExceptionEvent $event)
            {
                if ($event->getException() instanceof NotFoundHttpException) {
                    $event->setResponse(new Response('Not found', 404));
                }
            }
        };
    }
}

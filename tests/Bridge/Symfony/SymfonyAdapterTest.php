<?php declare(strict_types=1);

namespace Bref\Test\Bridge\Symfony;

use Bref\Bridge\Symfony\SymfonyAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Zend\Diactoros\ServerRequest;

class SymfonyAdapterTest extends TestCase
{
    private const ROUTE_WITHOUT_SESSION = '/';
    private const ROUTE_WITH_SESSION = '/session';
    private const ROUTE_NOT_FOUND = '/not-found';

    public function setUp()
    {
        parent::setUp();

        $fs = new Filesystem;
        $fs->remove([__DIR__ . '/var', __DIR__ . '/cache', __DIR__ . '/logs']);
    }

    public function test Symfony applications are adapted()
    {
        $adapter = new SymfonyAdapter($this->createKernel());

        $response = $adapter->handle(new ServerRequest([], [], self::ROUTE_WITHOUT_SESSION));

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Hello world!', (string) $response->getBody());
    }

    public function test 404 are PSR7 responses and not exceptions()
    {
        $adapter = new SymfonyAdapter($this->createKernel());

        $response = $adapter->handle(new ServerRequest([], [], self::ROUTE_NOT_FOUND));

        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals('Not found', (string) $response->getBody());
    }

    public function test a session is not created when sessions not used()
    {
        $adapter = new SymfonyAdapter($this->createKernel());

        $response = $adapter->handle(new ServerRequest([], [], self::ROUTE_WITHOUT_SESSION));

        self::assertArrayNotHasKey('Set-Cookie', $response->getHeaders());
    }

    public function test an active session is created when sessions used()
    {
        $adapter = new SymfonyAdapter($kernel = $this->createKernel());

        $response = $adapter->handle(new ServerRequest([], [], self::ROUTE_WITH_SESSION));

        /** @var SessionInterface $session */
        $session = $kernel->getContainer()->get('session');
        $symfonySessionId = $session->getId();

        self::assertEquals($symfonySessionId, (string) $response->getBody());
        self::assertEquals(
            sprintf('%s=%s; path=/', \session_name(), $symfonySessionId),
            $response->getHeaders()['Set-Cookie'][0]
        );
    }

    public function test an existing session is used when session provided()
    {
        $adapter = new SymfonyAdapter($this->createKernel());

        $response = $adapter->handle(
            new ServerRequest(
                [],
                [],
                self::ROUTE_WITH_SESSION,
                null,
                'php://input',
                [],
                [\session_name() => 'SESSIONID']
            )
        );

        self::assertArrayNotHasKey('Set-Cookie', $response->getHeaders());
        self::assertEquals('SESSIONID', (string) $response->getBody());
    }

    private function createKernel(): KernelInterface
    {
        $kernel = new class('dev', false) extends Kernel implements EventSubscriberInterface {
            use MicroKernelTrait;

            public function registerBundles(): array
            {
                return [new FrameworkBundle];
            }

            protected function configureContainer(ContainerBuilder $c): void
            {
                $c->register('session_storage', MockArraySessionStorage::class);

                $c->loadFromExtension('framework', [
                    'secret'  => 'foo',
                    'session' => [
                        'storage_id' => 'session_storage',
                    ],
                ]);
            }

            protected function configureRoutes(RouteCollectionBuilder $routes): void
            {
                $routes->add('/', 'kernel:testActionWithoutSession');
                $routes->add('/session', 'kernel:testActionWithSession');
            }

            public function testActionWithoutSession(): Response
            {
                return new Response('Hello world!');
            }

            public function testActionWithSession(Session $session): Response
            {
                $session->set('ACTIVATE', 'SESSIONS'); // ensure that Symfony starts/uses sessions

                return new Response($session->getId());
            }

            public static function getSubscribedEvents(): array
            {
                return [KernelEvents::EXCEPTION => 'onKernelException'];
            }

            /**
             * We have to handle NotFound exceptions ourselves because they are not handled by the micro-kernel
             */
            public function onKernelException(GetResponseForExceptionEvent $event): void
            {
                if ($event->getException() instanceof NotFoundHttpException) {
                    $event->setResponse(new Response('Not found', 404));
                }
            }

            public function getProjectDir()
            {
                return __DIR__;
            }
        };

        $kernel->boot();

        return $kernel;
    }
}

<?php declare(strict_types=1);

namespace Bref\Test\Bridge\Symfony;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollectionBuilder;

class TestKernel extends Kernel implements EventSubscriberInterface
{
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
}

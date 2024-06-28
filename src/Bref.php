<?php declare(strict_types=1);

namespace Bref;

use Bref\Listener\EventDispatcher;
use Bref\Runtime\FileHandlerLocator;
use Closure;
use Psr\Container\ContainerInterface;
use RuntimeException;

class Bref
{
    private static ?Closure $containerProvider = null;
    private static ?ContainerInterface $container = null;
    /**
     * TODO deprecate hooks when the event dispatcher is stable.
     */
    private static array $hooks = [
        'beforeStartup' => [],
        'beforeInvoke' => [],
    ];
    private static EventDispatcher $eventDispatcher;

    /**
     * Configure the container that provides Lambda handlers.
     *
     * @param Closure(): ContainerInterface $containerProvider Function that must return a `ContainerInterface`.
     */
    public static function setContainer(Closure $containerProvider): void
    {
        self::$containerProvider = $containerProvider;
    }

    /**
     * @internal This API is experimental and may change at any time.
     */
    public static function events(): EventDispatcher
    {
        if (! isset(self::$eventDispatcher)) {
            self::$eventDispatcher = new EventDispatcher;
        }
        return self::$eventDispatcher;
    }

    /**
     * Register a hook to be executed before the runtime starts.
     *
     * Warning: hooks are low-level extension points to be used by framework
     * integrations. For user code, it is not recommended to use them. Use your
     * framework's extension points instead.
     */
    public static function beforeStartup(Closure $hook): void
    {
        self::$hooks['beforeStartup'][] = $hook;
    }

    /**
     * Register a hook to be executed before any Lambda invocation.
     *
     * Warning: hooks are low-level extension points to be used by framework
     * integrations. For user code, it is not recommended to use them. Use your
     * framework's extension points instead.
     */
    public static function beforeInvoke(Closure $hook): void
    {
        self::$hooks['beforeInvoke'][] = $hook;
    }

    /**
     * @param 'beforeStartup'|'beforeInvoke' $hookName
     *
     * @internal Used by the Bref runtime
     */
    public static function triggerHooks(string $hookName): void
    {
        foreach (self::$hooks[$hookName] as $hook) {
            $hook();
        }
    }

    /**
     * @internal Used by the Bref runtime
     */
    public static function getContainer(): ContainerInterface
    {
        if (! self::$container) {
            if (self::$containerProvider) {
                self::$container = (self::$containerProvider)();
                if (! self::$container instanceof ContainerInterface) {
                    throw new RuntimeException('The closure provided to Bref\Bref::setContainer() did not return an instance of ' . ContainerInterface::class);
                }
            } else {
                self::$container = new FileHandlerLocator;
            }
        }

        return self::$container;
    }

    /**
     * @internal For tests.
     */
    public static function reset(): void
    {
        self::$containerProvider = null;
        self::$container = null;
        self::$hooks = [
            'beforeStartup' => [],
            'beforeInvoke' => [],
        ];
        self::$eventDispatcher = new EventDispatcher;
    }
}

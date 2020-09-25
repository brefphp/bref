<?php declare(strict_types=1);

namespace Bref;

use Bref\Runtime\FileHandlerLocator;
use Closure;
use Psr\Container\ContainerInterface;

/**
 * @experimental This class is not covered by backward compatibility yet.
 */
class Bref
{
    /** @var Closure|null */
    private static $containerProvider;
    /** @var ContainerInterface|null */
    private static $container;

    /**
     * Configure the container that provides Lambda handlers.
     *
     * @psalm-param Closure():\Psr\Container\ContainerInterface $containerProvider
     * @param Closure $containerProvider Function that must return a `ContainerInterface`.
     */
    public static function setContainer(Closure $containerProvider): void
    {
        self::$containerProvider = $containerProvider;
    }

    /**
     * @internal
     */
    public static function getContainer(): ContainerInterface
    {
        if (! self::$container) {
            if (! self::$containerProvider) {
                self::$container = (self::$containerProvider)();
                if (! self::$container instanceof ContainerInterface) {
                    throw new \RuntimeException('The closure provided to Bref\Bref::setContainer() did not return an instance of ' . ContainerInterface::class);
                }
            } else {
                self::$container = new FileHandlerLocator;
            }
        }

        return self::$container;
    }
}

<?php declare(strict_types=1);

namespace Bref;

use Bref\Runtime\FileHandlerLocator;
use Psr\Container\ContainerInterface;

/**
 * @experimental This class is not covered by backward compatibility yet.
 */
class Bref
{
    /** @var ContainerInterface|null */
    private static $container;

    /**
     * Set the container that provides Lambda handlers.
     */
    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * @internal
     */
    public static function getContainer(): ContainerInterface
    {
        return self::$container ?: new FileHandlerLocator;
    }
}

<?php declare(strict_types=1);

namespace Bref\Runtime;

use Psr\Container\ContainerInterface;

/**
 * Default Bref behavior to resolve "Lambda function handlers".
 *
 * With this behavior, Bref expects that handlers are PHP file names.
 * Whenever a Lambda function executes, this class will `require` that PHP file
 * and return what the file returns.
 *
 * @see \Bref\Bref::setContainer()
 *
 * @internal
 */
class FileHandlerLocator implements ContainerInterface
{
    /** @var string */
    private $directory;

    public function __construct(string $directory = null)
    {
        $this->directory = $directory ?: $_SERVER['LAMBDA_TASK_ROOT'];
    }

    public function get($id)
    {
        $handlerFile = $this->directory . '/' . $id;
        if (! is_file($handlerFile)) {
            throw new HandlerNotFound("Handler `$handlerFile` doesn't exist");
        }

        /** @noinspection PhpIncludeInspection */
        $handler = require $handlerFile;

        if (! (is_object($handler) || is_array($handler))) {
            throw new HandlerNotFound("Handler `$handlerFile` must return a function or object handler. See https://bref.sh/docs/runtimes/function.html");
        }

        return $handler;
    }

    public function has($id): bool
    {
        return is_file($this->directory . '/' . $id);
    }
}

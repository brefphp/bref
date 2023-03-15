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
 */
class FileHandlerLocator implements ContainerInterface
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $directory = $directory ?: ($_SERVER['LAMBDA_TASK_ROOT'] ?? null);

        // When running locally (`serverless bref:local` CLI command) `LAMBDA_TASK_ROOT` is undefined
        $this->directory = $directory ?: getcwd();
    }

    /**
     * {@inheritDoc}
     */
    public function get($id)
    {
        $handlerFile = $this->directory . '/' . $id;
        if (! is_file($handlerFile)) {
            throw new HandlerNotFound("Handler `$handlerFile` doesn't exist");
        }

        $handler = require $handlerFile;

        if (! (is_object($handler) || is_array($handler))) {
            throw new HandlerNotFound("Handler `$handlerFile` must return a function or object handler. See https://bref.sh/docs/runtimes/function.html");
        }

        return $handler;
    }

    /**
     * {@inheritDoc}
     */
    public function has($id): bool
    {
        return is_file($this->directory . '/' . $id);
    }
}

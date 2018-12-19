<?php declare(strict_types=1);

namespace Bref\Runtime;

use hollodotme\FastCGI\Requests\AbstractRequest;

/**
 * We have to extend `AbstractRequest` because else it requires us to choose a specific implementation to use:
 * POST, GET, PUT, etc.
 *
 * Additionally there is no `OptionRequest` implemented in that FastCGI library. So for now let's roll our own
 * implementation.
 */
class FastCgiRequest extends AbstractRequest
{
    /** @var string */
    private $method;

    public function __construct(string $method, string $scriptFilename, string $content)
    {
        $this->method = $method;
        parent::__construct($scriptFilename, $content);
    }

    public function getRequestMethod(): string
    {
        return $this->method;
    }
}

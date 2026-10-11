<?php declare(strict_types=1);

namespace Bref\FpmRuntime\FastCgi;

use hollodotme\FastCGI\Interfaces\ComposesRequestContent;
use hollodotme\FastCGI\Requests\AbstractRequest;

/**
 * @internal
 */
final class FastCgiRequest extends AbstractRequest
{
    public function __construct(
        private string $method,
        string $scriptFilename,
        string $content
    ) {
        $this->method = $method;
        parent::__construct($scriptFilename, new class($content) implements ComposesRequestContent {
            public function __construct(private string $content)
            {
            }

            public function getContentType(): string
            {
                // Same default as fast-cgi-client 3.x
                return 'application/x-www-form-urlencoded';
            }

            public function getContent(): string
            {
                return $this->content;
            }
        });
    }

    public function getRequestMethod(): string
    {
        return $this->method;
    }

    public function getServerSoftware(): string
    {
        return 'bref';
    }
}

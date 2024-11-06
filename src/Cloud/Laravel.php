<?php declare(strict_types=1);

namespace Bref\Cloud;

use Bref\Cloud;

class Laravel
{
    /** @var string[] */
    public array $patterns = [
        '**',
        '!.git/**',
        '!database/*.sqlite',
        '!node_modules/**',
        '!public/build/**',
        '!public/storage/**',
        '!resources/assets/**',
        '!storage/**',
        // Force to include the public and private keys required by Laravel Passport
        'storage/oauth-private.key',
        'storage/oauth-public.key',
        '!tests/**',
    ];

    /** @var array<string, mixed> */
    public array $variables = [
        'APP_DEBUG' => false,
        'LOG_CHANNEL' => 'stderr',
        'SESSION_DRIVER' => 'cookie',
    ];

    /**
     * @param '8.0'|'8.1'|'8.2'|'8.3'|'8.4' $php The PHP version to use.
     * @param string[] $patterns Path patterns to include or exclude from the deployment.
     * @param array<string, mixed> $variables Environment variables to set in the Lambda.
     */
    public function __construct(
        public string $name,
        public string $php = '8.2',
        public string $path = '.',
        array $patterns = [],
        public string $assets = 'public',
        array $variables = [],
        public int $memory = 1024,
        public int $timeout = 28,
        public bool $scheduler = false,
        public bool $queue = false,
    ) {
        $this->patterns = array_merge($this->patterns, $patterns);

        $this->variables['APP_ENV'] = Cloud::environment();
        $this->variables = array_merge($this->variables, $variables);

        Cloud::app($this);
    }

    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @internal
     */
    public function toArray(): array
    {
        $config = [
            'name' => $this->name,
            'php' => $this->php,
            'package' => Cloud::package($this->path, $this->patterns),
            'variables' => $this->variables,
            'memory' => $this->memory,
            'timeout' => $this->timeout,
            'scheduler' => $this->scheduler,
            'queue' => $this->queue,
        ];

        // Only package assets if the `assets` directory exists and contains files
        if (is_dir($this->assets) && count(scandir($this->assets)) > 2) {
            $config['assets'] = Cloud::package($this->assets, ['**']);
        }

        return $config;
    }
}

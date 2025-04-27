<?php declare(strict_types=1);

namespace Bref\Cloud;

use Bref\Cloud;

class Laravel
{
    /** @var string[] */
    public array $patterns = [
        '**',
        '!.idea/**',
        '!.bref/**',
        '!.git/**',
        '!.serverless/**',
        '!database/*.sqlite',
        '!node_modules/**',
        // Exclude assets except for the manifest file
        '!public/build/**',
        'public/build/manifest.json',
        '!public/storage/**',
        '!resources/assets/**',
        '!resources/js/**',
        '!resources/css/**',
        '!resources/images/**',
        '!storage/**',
        // Force to include the public and private keys required by Laravel Passport
        'storage/oauth-private.key',
        'storage/oauth-public.key',
        '!tests/**',
    ];

    /** @var array<string, string> */
    public array $variables = [
        'APP_DEBUG' => '0',
        'LOG_CHANNEL' => 'stderr',
        'SESSION_DRIVER' => 'cookie',
        'FILESYSTEM_DISK' => 's3',
        'FILESYSTEM_CLOUD' => 's3',
        'FILESYSTEM_DRIVER' => 's3',
    ];

    /**
     * @param '8.0'|'8.1'|'8.2'|'8.3'|'8.4' $php The PHP version to use.
     * @param string[] $patterns Path patterns to include or exclude from the deployment.
     * @param array<string, mixed> $variables Environment variables to set in the Lambda.
     */
    public function __construct(
        public string $name,
        public string $php = '8.3',
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
            'type' => 'laravel',
            'php' => $this->php,
            'package' => Cloud::package($this->path, $this->patterns),
            'variables' => $this->variables,
            'memory' => $this->memory,
            'timeout' => $this->timeout,
            'scheduler' => $this->scheduler,
            'queue' => $this->queue,
        ];

        $config = $this->packageAssets($config);

        return $config;
    }

    private function packageAssets(array $config): array
    {
        if (! is_dir($this->assets)) {
            return $config;
        }

        // Ignore files:
        // - .
        // - ..
        // - PHP files
        // - symlinks (e.g. public/storage)
        // - .htaccess
        $fileList = scandir($this->assets);
        $fileList = array_filter($fileList, fn($file) =>
            ! in_array($file, ['.', '..', '.htaccess'], true)
            && ! preg_match('/\.php$/', $file)
            && ! is_link($file)
        );
        if (empty($fileList)) {
            return $config;
        }

        $config['assets'] = Cloud::package($this->assets, [
            '**',
            '!*.php',
            '!.htaccess',
            'hot',
            // Ignore the public storage symlink
            '!storage',
        ]);
        $config['routing'] = [];
        foreach ($fileList as $file) {
            $config['routing'][$file] = "/$file";
        }

        return $config;
    }
}

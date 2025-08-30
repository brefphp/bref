<?php declare(strict_types=1);

namespace Bref\Cloud;

use Bref\Cloud;
use InvalidArgumentException;

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
     * @param '8.2'|'8.3'|'8.4'|'8.5' $php The PHP version to use.
     * @param string[] $patterns Path patterns to include or exclude from the deployment.
     * @param array<string, mixed> $variables Environment variables to set in the Lambda.
     */
    public function __construct(
        public string $name,
        public string $php,
        public string $rootPath = '.',
        array $patterns = [],
        public string $assets = 'public',
        array $variables = [],
        public int $memory = 1024,
        public int $timeout = 28,
        public bool $scheduler = false,
        public bool $queue = false,
    ) {
        // Ensures the root path is never empty
        if (empty($this->rootPath)) {
            $this->rootPath = '.';
        }

        if (! in_array($this->php, ['8.2', '8.3', '8.4', '8.5'], true)) {
            throw new InvalidArgumentException("Invalid PHP version '$this->php', must be one of '8.2', '8.3', '8.4', or '8.5'.");
        }

        $this->patterns = array_merge($this->patterns, $patterns);

        $this->variables['APP_ENV'] = Cloud::environment();
        if (class_exists(\Bref\Monolog\CloudWatchFormatter::class)) {
             $this->variables['LOG_STDERR_FORMATTER'] = \Bref\Monolog\CloudWatchFormatter::class;
        }
        $this->variables = array_merge($this->variables, $variables);

        Cloud::app($this);
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
            'package' => Cloud::package($this->rootPath, $this->patterns),
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
        $assetsPath = $this->rootPath . '/' . $this->assets;
        if (! is_dir($assetsPath)) {
            return $config;
        }

        // Ignore files:
        // - .
        // - ..
        // - PHP files
        // - symlinks (e.g. public/storage)
        // - .htaccess
        $fileList = scandir($assetsPath);
        $fileList = array_filter($fileList, fn($file) =>
            ! in_array($file, ['.', '..', '.htaccess'], true)
            && ! preg_match('/\.php$/', $file)
            && ! is_link($file)
        );
        if (empty($fileList)) {
            return $config;
        }

        $config['assets'] = Cloud::package($assetsPath, [
            '**',
            '!*.php',
            '!.htaccess',
            'hot',
            // Ignore the public storage symlink
            '!storage',
        ]);
        $config['routing'] = [];
        foreach ($fileList as $fileName) {
            $relativePath = $assetsPath . '/' . $fileName;
            if (is_dir($relativePath)) {
                $config['routing'][$fileName . '/*'] = "/$fileName";
            } else {
                $config['routing'][$fileName] = "/$fileName";
            }
        }

        return $config;
    }
}

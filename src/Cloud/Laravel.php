<?php declare(strict_types=1);

namespace Bref\Cloud;

use Bref\Cloud;
use Bref\Monolog\CloudWatchFormatter;
use InvalidArgumentException;

class Laravel
{
    /** @var string[] */
    public array $patterns = [
        '**',
        '!.idea/**',
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

    /**
     * @internal Only for Bref Cloud's own tests: no CloudFront distribution, which takes 5 to 10
     * minutes to create and as long to delete. The application is then served by its Function URL.
     */
    public bool $cdn = true;

    /** @var array{name: string, migrate: bool}|null */
    private ?array $database = null;

    /** @var list<string> */
    private array $beforeDeploy = [];

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
     * @param string                                                                                $php          The PHP version to use: '8.2', '8.3', '8.4' or '8.5'.
     * @param string|null                                                                           $region       The AWS region to deploy to, e.g. 'eu-west-3'. Defaults to us-east-1.
     * @param string[]                                                                              $patterns     Path patterns to include or exclude from the deployment.
     * @param array<string, mixed>                                                                  $variables    Environment variables to set in the Lambda.
     * @param 'arm'|'x86'|null                                                                      $architecture The architecture of all the functions. Defaults to ARM.
     * @param array{memory?: int, timeout?: int}                                                    $web          Settings of the function that serves HTTP requests.
     * @param bool|array<int|string, string|array{memory?: int, timeout?: int, maxInstances?: int}> $queue        `true` for the `default` queue, or queue names, e.g. `['default', 'emails' => ['memory' => 2048]]`.
     * @param array{memory?: int, timeout?: int, maxInstances?: int}                                $worker       Settings of the queue workers.
     * @param array{memory?: int, timeout?: int}                                                    $artisan      Settings of the function that runs the scheduler and commands.
     */
    public function __construct(
        public string $name,
        public string $php,
        public ?string $region = null,
        public string $rootPath = '.',
        array $patterns = [],
        public string $assets = 'public',
        array $variables = [],
        public bool $scheduler = false,
        public bool|array $queue = false,
        public ?string $architecture = null,
        public array $web = [],
        public array $worker = [],
        public array $artisan = [],
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
        if (class_exists(CloudWatchFormatter::class)) {
            $this->variables['LOG_STDERR_FORMATTER'] = CloudWatchFormatter::class;
        }
        $this->variables = array_merge($this->variables, $variables);

        Cloud::app($this);
    }

    /**
     * Use a database created in the Bref Cloud dashboard: Bref Cloud sets the `DB_*` variables, runs the
     * functions in the database's network, and runs `php artisan migrate --force` on each deployment.
     *
     * @param string $name    The name of the database in the Bref Cloud dashboard.
     * @param bool   $migrate Whether to run the migrations on each deployment, before the new code receives traffic.
     */
    public function useDatabase(string $name, bool $migrate = true): self
    {
        $this->database = ['name' => $name, 'migrate' => $migrate];

        return $this;
    }

    /**
     * Run Artisan commands with the new code before it receives traffic, after the migrations of
     * `useDatabase()`: when one fails, the deployment fails and the previous version stays live.
     *
     * @param string ...$commands Artisan commands without `php artisan`, e.g. `cache:clear`. They run on every deployment: they must be safe to run again.
     */
    public function beforeDeploy(string ...$commands): self
    {
        $this->beforeDeploy = [...$this->beforeDeploy, ...$commands];

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
            'region' => $this->region,
            'package' => Cloud::package($this->rootPath, $this->patterns),
            'variables' => $this->variables,
            'scheduler' => $this->scheduler,
            'queue' => $this->queue,
        ];
        // Bref Cloud has the defaults, and validates the settings
        foreach (['architecture', 'web', 'worker', 'artisan', 'database', 'beforeDeploy'] as $option) {
            if ($this->$option !== null && $this->$option !== []) {
                $config[$option] = $this->$option;
            }
        }

        if ($this->cdn) {
            $config = $this->packageAssets($config);
        } else {
            // Nothing would serve the assets
            $config['cdn'] = false;
        }

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
        $fileList = array_filter(
            $fileList,
            fn ($file) => ! in_array($file, ['.', '..', '.htaccess'], true) && ! preg_match('/\.php$/', $file) && ! is_link($file),
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

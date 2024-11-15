<?php declare(strict_types=1);

namespace Bref;

use RuntimeException;

class Cloud
{
    private static self $instance;

    private static ?string $team;
    private static string $environment;
    private static array $apps = [];
    /** @var array<string, array{path: string, patterns: string[]}> */
    private static array $packages = [];

    /**
     * Get or set the team.
     *
     * If a team is set via the CLI it will take precedence.
     */
    public static function team(?string $team = null): string
    {
        self::init();

        if ($team !== null) {
            // If a team is forced, it takes precedence
            if (isset($_SERVER['BREF_CLI_TEAM']) && $_SERVER['BREF_CLI_TEAM'] !== '') {
                $team = $_SERVER['BREF_CLI_TEAM'];
            }
            self::$team = $team;
        } else if (! isset(self::$team)) {
            throw new RuntimeException('A Bref Cloud team must be set first: call `Bref\Cloud::team(...)`');
        }

        return self::$team;
    }

    /**
     * Get the app's environment.
     */
    public static function environment(): string
    {
        self::init();

        if (! isset(self::$environment)) {
            self::$environment = $_SERVER['BREF_CLI_ENV'] ?? 'dev';
        }

        return self::$environment;
    }

    /**
     * @internal
     */
    public static function app($app): void
    {
        if (in_array($app, self::$apps, true)) {
            return;
        }

        self::$apps[] = $app;
    }

    /**
     * @internal
     *
     * @param string[] $patterns
     */
    public static function package(string $path, array $patterns): string
    {
        self::init();

        // Random string of 8 characters (prefixed with "a" to ensure it's not cast to a number)
        $uniqueId = 'a' . bin2hex(random_bytes(4));
        $packageKey = "\${packages:$uniqueId}";

        self::$packages[$uniqueId] = [
            'path' => realpath($path),
            'patterns' => $patterns,
        ];

        return $packageKey;
    }

    private static function init(): void
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }
    }

    private static function toArray(): array
    {
        $apps = array_map(fn($app) => $app->toArray(), self::$apps);

        return [
            'team' => self::team(),
            'environment' => self::environment(),
            'packages' => self::$packages,
            'apps' => $apps,
        ];
    }

    /**
     * @internal
     */
    public function __destruct()
    {
        echo json_encode(self::toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . PHP_EOL;
    }
}

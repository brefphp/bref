<?php declare(strict_types=1);

namespace Bref\Console\Command;

use Composer\InstalledVersions;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class Init extends Command
{
    /**
     * Symfony console input/output handler
     *
     * @var SymfonyStyle
     */
    private $io;

    /**
     * Absolute path pointing into project's
     * template directory
     *
     * @var string
     */
    private $rootPath;

    protected function configure(): void
    {
        $this->setName('init');
        $this->setDescription("Creates a serverless.yml and index.php in the current directory");
        $this->setDefinition([
            new InputOption("name", null, InputOption::VALUE_REQUIRED, "Name of the project"),
            new InputOption("region", null, InputOption::VALUE_REQUIRED, "Region of the project"),
            new InputOption("runtime", null, InputOption::VALUE_REQUIRED, "Which runtime to use"),
            new InputOption("overwrite-php", null, InputOption::VALUE_REQUIRED, "Should overwrite index.php if exists", true, [true, false]),
            new InputOption("overwrite-serverless", null, InputOption::VALUE_REQUIRED, "Should overwrite serverless.yml if exists", true, [true, false]),
        ]);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getOption("name");
        if (is_null($name)) {
            // Get the current folder name to use it as default name
            $currentWorkingDirectory = realpath(".");
            $name = trim(strtolower(basename($currentWorkingDirectory)));
            $name = trim(preg_replace("/[^a-z0-9-]+/", "-", $name), "-");
        }

        $name = $io->ask("Name of the project", $name, [$this, "validateName"]);
        $input->setOption("name", $name);

        $region = $input->getOption("region");
        if (is_null($region)) {
            $region = $_ENV["AWS_DEFAULT_REGION"] ?? $_ENV["AWS_REGION"] ?? "us-east-1";
        }

        $layersJson = file_get_contents(dirname(__DIR__, 3) . '/layers.json');
        $layers = json_decode($layersJson, true, 512, JSON_THROW_ON_ERROR);
        $supportedRegions = array_keys(array_merge(...array_values($layers)));
        sort($supportedRegions);

        $region = $io->ask("Region to deploy the project to", $region, function ($answer) use ($supportedRegions) {
            if (!in_array($answer, $supportedRegions, true)) {
                throw new Exception("Region must be one of the following:\n" . implode(", ", $supportedRegions));
            }

            return $answer;
        });
        $input->setOption("region", $region);

        $runtime = $input->getOption("runtime");
        $supportedRuntimes = [
            "webapp" => "Web Application (PHP-FPM)",
            "function" => "Event-Driven Functions",
        ];
        if (!in_array($runtime, array_keys($supportedRuntimes), true)) {
            $runtime = "function";
        }

        $runtime = $io->choice(
            "What kind of lambda do you want to create?",
            $supportedRuntimes,
            $runtime
        );
        $input->setOption("runtime", $runtime);

        if (file_exists("index.php")) {
            $overwriteIndex = (bool)$input->getOption("overwrite-php");
            $overwriteIndex = $io->confirm("<comment>index.php already exists, do you want to overwrite it?</comment>", $overwriteIndex);
            $input->setOption("overwrite-php", $overwriteIndex);
        }

        if (file_exists("serverless.yml")) {
            $overwriteServerless = (bool)$input->getOption("overwrite-serverless");
            $overwriteServerless = $io->confirm("<comment>serverless.yml already exists, do you want to overwrite it?</comment>", $overwriteServerless);
            $input->setOption("overwrite-serverless", $overwriteServerless);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = $io = new SymfonyStyle($input, $output);

        $name = $input->getOption("name");
        $region = $input->getOption("region");
        $runtime = $input->getOption("runtime");
        $overwritePhp = (bool)$input->getOption("overwrite-php");
        $overwriteSls = (bool)$input->getOption("overwrite-serverless");

        if (
            !$input->isInteractive() &&
            (is_null($name) || is_null($region) || is_null($runtime))
        ) {
            throw new Exception("You have to run this command in interactive mode or specify data using --name, --region, etc.");
        }

        $exeFinder = new ExecutableFinder;
        if (!$exeFinder->find('serverless')) {
            $io->warning(
                'The `serverless` command is not installed.' . PHP_EOL .
                'You will not be able to deploy your application unless it is installed' . PHP_EOL .
                'Please follow the instructions at https://bref.sh/docs/installation.html' . PHP_EOL .
                'If you have the `serverless` command available elsewhere (eg in a Docker container) you can ignore this warning'
            );
        }

        $this->rootPath = dirname(__DIR__, 3) . "/template/$runtime";

        $variables = [
            "TPL_PHP_VERSION_PRETTY" => PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION,
            "TPL_PHP_VERSION" => PHP_MAJOR_VERSION . PHP_MINOR_VERSION,
            "TPL_BREF_VERSION" => InstalledVersions::getVersion("bref/bref"),
            "TPL_SERVICE_NAME" => $name,
            "TPL_REGION" => $region,
        ];

        $this->createFile("index.php", $variables, $overwritePhp);
        $this->createFile("serverless.yml", $variables, $overwriteSls);

        $io->success("Project initialized and ready to test or deploy.");

        return 0;
    }

    /**
     * Creates files from the template directory and automatically adds
     * them to git
     */
    private function createFile(string $file, array $variables, bool $shouldOverwrite = true): void
    {
        $this->io->writeln("Creating $file:");

        if (file_exists($file) && $shouldOverwrite === false) {
            return;
        }

        $template = file_get_contents("$this->rootPath/$file");
        $template = str_replace(array_keys($variables), array_values($variables), $template);

        file_put_contents($file, $template);

        $this->io->writeln("\t- <info>$file successfully created</info>.");

        /*
         * We check if this is a git repository to automatically add file to git.
         */
        if ((new Process(['git', 'rev-parse', '--is-inside-work-tree']))->run() === 0) {
            (new Process(['git', 'add', $file]))->run();
            $this->io->writeln("\t- <info>added to git automatically</info>.");
        }
    }

    /**
     * @throws Exception
     */
    public function validateName(string $name): string
    {
        $regex = "/^[a-zA-Z][0-9a-zA-Z-]+$/";
        if (!preg_match($regex, $name)) {
            throw new Exception("Project name must match pattern \"$regex\"");
        }

        return $name;
    }
}

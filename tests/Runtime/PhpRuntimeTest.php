<?php declare(strict_types=1);

namespace Bref\Test\Runtime;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class PhpRuntimeTest extends TestCase
{
    public function test invocation without event()
    {
        $this->invokeLambda(null, 'Hello world');
    }

    public function test invocation with event()
    {
        $this->invokeLambda([
            'name' => 'Abby',
        ], 'Hello Abby');
    }

    public function test php extensions()
    {
        $this->invokeLambda([
            'extensions' => true,
        ], [
            'Core',
            'date',
            'libxml',
            'openssl',
            'pcre',
            'sqlite3',
            'zlib',
            'ctype',
            'curl',
            'dom',
            'hash',
            'fileinfo',
            'filter',
            'ftp',
            'gettext',
            'SPL',
            'iconv',
            'json',
            'mbstring',
            'pcntl',
            'session',
            'PDO',
            'pdo_sqlite',
            'standard',
            'posix',
            'readline',
            'Reflection',
            'Phar',
            'SimpleXML',
            'sodium',
            'exif',
            'tokenizer',
            'xml',
            'xmlreader',
            'xmlwriter',
            'mysqlnd',
            'Zend OPcache',
        ]);
    }

    /**
     * @param mixed $event
     * @param mixed $expectedResult
     */
    private function invokeLambda($event, $expectedResult): void
    {
        $command = ['sam', 'local', 'invoke', 'PhpFunction', '--region', 'us-east-1'];
        if ($event === null) {
            $command[] = '--no-event';
        }
        $process = new Process($command);
        $process->setWorkingDirectory(__DIR__);
        $process->setTimeout(0);
        if ($event !== null) {
            $process->setInput(json_encode($event));
        }
        $process->mustRun();

        $output = explode("\n", trim($process->getOutput()));
        $lastLine = end($output);
        $result = json_decode($lastLine, true);

        self::assertSame($expectedResult, $result, $process->getErrorOutput());
    }
}

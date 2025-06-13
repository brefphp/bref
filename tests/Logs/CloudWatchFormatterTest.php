<?php declare(strict_types=1);

namespace Bref\Test\Logs;

use Bref\Logs\CloudWatchFormatter;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class CloudWatchFormatterTest extends TestCase
{
    private Logger $logger;
    /** @var resource */
    private $logs;

    public function setUp(): void
    {
        parent::setUp();
        $this->logger = new Logger('default');
        $this->logs = fopen('php://memory', 'wb+');
        $handler = new StreamHandler($this->logs);
        $handler->setFormatter(new CloudWatchFormatter);
        $this->logger->pushHandler($handler);
    }

    public function test simple message(): void
    {
        $this->logger->info('Test message');

        $this->assertEquals("INFO\tTest message\t" . json_encode([
            'message' => 'Test message',
            'level' => 'INFO',
        ], JSON_THROW_ON_ERROR) . "\n", $this->getLogs());
    }

    public function test with context(): void
    {
        $this->logger->info('Test message', ['key' => 'value']);

        $this->assertEquals("INFO\tTest message\t" . json_encode([
            'message' => 'Test message',
            'level' => 'INFO',
            'context' => ['key' => 'value'],
        ], JSON_THROW_ON_ERROR) . "\n", $this->getLogs());
    }

    public function test multiline message(): void
    {
        $this->logger->error("Test\nmessage");

        $this->assertEquals("ERROR\tTest message\t" . json_encode([
            'message' => "Test\nmessage",
            'level' => 'ERROR',
        ], JSON_THROW_ON_ERROR) . "\n", $this->getLogs());
    }

    public function test with exception(): void
    {
        $e = new Exception('Test error');
        $this->logger->info('Test message', ['exception' => $e]);

        $this->assertStringStartsWith('INFO	Test message	{"message":"Test message","level":"INFO","exception":{"class":"Exception","message":"Test error","code":0,"file":', $this->getLogs());
    }

    private function getLogs(): string
    {
        rewind($this->logs);
        return stream_get_contents($this->logs);
    }
}

<?php declare(strict_types=1);

namespace Bref\Test\Handler;

use Bref\Context\Context;
use Bref\Event\Http\FpmHandler;
use PHPStan\Testing\TestCase;

final class FpmHandlerLoadBalancerTest extends TestCase
{
    /** @var FpmHandler|null */
    private $fpm;
    /** @var Context */
    private $fakeContext;

    public function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->fakeContext = new Context('abc', time(), 'abc', 'abc');
    }

    public function tearDown(): void
    {
        $this->fpm->stop();
        ob_end_clean();
    }

    public function test request with multivalues query string have basic support()
    {
        $loadBalancerEvent = file_get_contents(__DIR__ . '/PhpFpm/alb-query-string-multivalue.json');

        $response = $this->get('request.php', json_decode($loadBalancerEvent, true));

        $body = json_decode($response['body'], true);

        self::assertSame($body['$_GET']['date']['from'], '2019-07-18');
        self::assertSame($body['$_GET']['date']['to'], '2020-07-16');
        self::assertSame($body['$_GET']['date']['type'], 'date_email_sent');
        self::assertSame($body['$_GET']['filter']['destination'][0], '101');
    }

    private function get(string $file, ?array $event = null): array
    {
        $this->startFpm(__DIR__ . '/PhpFpm/' . $file);

        return $this->fpm->handle($event ?? [
                'version' => '1.0',
                'httpMethod' => 'GET',
            ], $this->fakeContext);
    }

    private function startFpm(string $handler): void
    {
        if ($this->fpm) {
            $this->fpm->stop();
        }
        $this->fpm = new FpmHandler($handler, __DIR__ . '/PhpFpm/php-fpm.conf');
        $this->fpm->start();
    }
}

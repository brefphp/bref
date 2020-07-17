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

    /**
     * This one tests query strings unencoded coming from ALB. Only the last value sent is kept by ALB.
     * Multi values parameters are lost. The fixture contains only the actual data that ALB ended up
     * sending to Lambda. conditions[survey][0][values][] was suppose to have multiple values, but
     * only 23 was kept.
     */
    public function test alb query strings()
    {
        $loadBalancerEvent = file_get_contents(__DIR__ . '/PhpFpm/alb-query-string.json');

        $response = $this->get('request.php', json_decode($loadBalancerEvent, true));

        $body = json_decode($response['body'], true);

        self::assertSame('segment_a', $body['$_GET']['conditions']['survey'][0]['field']);
        self::assertSame('=', $body['$_GET']['conditions']['survey'][0]['operator']);
        self::assertSame('23', $body['$_GET']['conditions']['survey'][0]['values'][0]);
        self::assertSame('2020-06-18', $body['$_GET']['period']['survey']['start']);
        self::assertSame('2020-07-17', $body['$_GET']['period']['survey']['end']);
        self::assertSame('date_email_sent', $body['$_GET']['period']['survey']['field']);
    }

    /**
     * Same as `test alb query strings`. However, this time the query strings are URL encoded.
     * Multi value is not enabled.
     */
    public function test alb query strings encoded()
    {
        $loadBalancerEvent = file_get_contents(__DIR__ . '/PhpFpm/alb-query-string-encoded.json');

        $response = $this->get('request.php', json_decode($loadBalancerEvent, true));

        $body = json_decode($response['body'], true);

        self::assertSame('2019-07-19', $body['$_GET']['date']['from']);
        self::assertSame('2020-07-17', $body['$_GET']['date']['to']);
        self::assertSame('date_email_sent', $body['$_GET']['date']['type']);
        self::assertSame('101', $body['$_GET']['filter']['destination'][0]);
    }

    public function test alb query strings mixed encoding()
    {
        $loadBalancerEvent = file_get_contents(__DIR__ . '/PhpFpm/alb-query-string-mixed-encoding.json');

        $response = $this->get('request.php', json_decode($loadBalancerEvent, true));

        $body = json_decode($response['body'], true);

        self::assertSame('abc123!@#%', $body['$_GET']['encoded_value'][0]);
        self::assertSame('106', $body['$_GET']['filter']['destination'][0]);
    }

    public function test alb query strings with multi value enabled()
    {
        $loadBalancerEvent = file_get_contents(__DIR__ . '/PhpFpm/alb-multi-value-query-string.json');

        $response = $this->get('request.php', json_decode($loadBalancerEvent, true));

        $body = json_decode($response['body'], true);

        self::assertSame('segment_a', $body['$_GET']['conditions']['survey'][0]['field']);
        self::assertSame('=', $body['$_GET']['conditions']['survey'][0]['operator']);
        self::assertSame('64', $body['$_GET']['conditions']['survey'][0]['values'][0]);
        self::assertSame('40', $body['$_GET']['conditions']['survey'][0]['values'][1]);
        self::assertSame('23', $body['$_GET']['conditions']['survey'][0]['values'][2]);
        self::assertSame('2020-06-18', $body['$_GET']['period']['survey']['start']);
        self::assertSame('2020-07-17', $body['$_GET']['period']['survey']['end']);
        self::assertSame('date_email_sent', $body['$_GET']['period']['survey']['field']);
    }

    public function test alb query strings encoded with multi value enabled()
    {
        $loadBalancerEvent = file_get_contents(__DIR__ . '/PhpFpm/alb-multi-value-query-string-encoded.json');

        $response = $this->get('request.php', json_decode($loadBalancerEvent, true));

        $body = json_decode($response['body'], true);

        self::assertSame('101', $body['$_GET']['filter']['destination'][0]);
        self::assertSame('103', $body['$_GET']['filter']['destination'][1]);
        self::assertSame('106', $body['$_GET']['filter']['destination'][2]);
        self::assertSame('month', $body['$_GET']['date_increment']);
    }

    public function test alb query strings with multi value enabled includes numeric keys()
    {
        $loadBalancerEvent = file_get_contents(__DIR__ . '/PhpFpm/alb-multi-value-query-string-with-numeric-keys.json');

        $response = $this->get('request.php', json_decode($loadBalancerEvent, true));

        $body = json_decode($response['body'], true);

        self::assertSame('segment_a', $body['$_GET']['conditions']['survey'][0]['field']);
        self::assertSame('=', $body['$_GET']['conditions']['survey'][0]['operator']);
        self::assertSame('64', $body['$_GET']['conditions']['survey'][0]['values'][0]);
        self::assertSame('40', $body['$_GET']['conditions']['survey'][0]['values'][1]);
        self::assertSame('23', $body['$_GET']['conditions']['survey'][0]['values'][2]);

        self::assertSame('segment_c', $body['$_GET']['conditions']['survey'][1]['field']);
        self::assertSame('=', $body['$_GET']['conditions']['survey'][1]['operator']);
        self::assertSame('10', $body['$_GET']['conditions']['survey'][1]['values'][0]);
        self::assertSame('9', $body['$_GET']['conditions']['survey'][1]['values'][1]);
        self::assertSame('6', $body['$_GET']['conditions']['survey'][1]['values'][2]);

        self::assertSame('2020-06-18', $body['$_GET']['period']['survey']['start']);
        self::assertSame('2020-07-17', $body['$_GET']['period']['survey']['end']);
        self::assertSame('date_email_sent', $body['$_GET']['period']['survey']['field']);
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

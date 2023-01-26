<?php declare(strict_types=1);

namespace Bref\Test\Secrets;

use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\Ssm\Result\GetParametersResult;
use AsyncAws\Ssm\SsmClient;
use AsyncAws\Ssm\ValueObject\Parameter;
use Bref\Secrets\Secrets;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SecretsTest extends TestCase
{
    public function test decrypts env variables(): void
    {
        putenv('SOME_VARIABLE=bref-ssm:/some/parameter');
        putenv('SOME_OTHER_VARIABLE=helloworld');

        // Sanity checks
        $this->assertSame('bref-ssm:/some/parameter', getenv('SOME_VARIABLE'));
        $this->assertSame('helloworld', getenv('SOME_OTHER_VARIABLE'));

        Secrets::decryptSecretEnvironmentVariables($this->mockSsmClient());

        $this->assertSame('foobar', getenv('SOME_VARIABLE'));
        $this->assertSame('foobar', $_SERVER['SOME_VARIABLE']);
        $this->assertSame('foobar', $_ENV['SOME_VARIABLE']);
        // Check that the other variable was not modified
        $this->assertSame('helloworld', getenv('SOME_OTHER_VARIABLE'));
    }

    public function test throws a clear error message on missing permissions(): void
    {
        putenv('SOME_VARIABLE=bref-ssm:/some/parameter');

        $ssmClient = $this->getMockBuilder(SsmClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ssmClient->method('getParameters')
            ->willThrowException(new RuntimeException('<original message>', 400));

        $this->expectExceptionMessage("Bref was not able to resolve secrets contained in environment variables from SSM because of a permissions issue with the SSM API. Did you add IAM permissions in serverless.yml to allow Lambda to access SSM? (docs: https://bref.sh/docs/environment/variables.html#at-deployment-time).\nFull exception message: <original message>");
        Secrets::decryptSecretEnvironmentVariables($ssmClient);
    }

    private function mockSsmClient(): SsmClient
    {
        $ssmClient = $this->getMockBuilder(SsmClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParameters'])
            ->getMock();

        $result = ResultMockFactory::create(GetParametersResult::class, [
            'Parameters' => [
                new Parameter([
                    'Name' => '/some/parameter',
                    'Value' => 'foobar',
                ]),
            ],
        ]);

        $ssmClient->expects($this->once())
            ->method('getParameters')
            ->with([
                'Names' => ['/some/parameter'],
                'WithDecryption' => true,
            ])
            ->willReturn($result);

        return $ssmClient;
    }
}

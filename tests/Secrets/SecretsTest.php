<?php declare(strict_types=1);

namespace Bref\Test\Secrets;

use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\Ssm\Result\GetParametersResult;
use AsyncAws\Ssm\SsmClient;
use AsyncAws\Ssm\ValueObject\Parameter;
use Bref\Secrets\Secrets;
use PHPUnit\Framework\TestCase;

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
        putenv('SOME_VARIABLE=bref-ssm:/app/test');

        $ssmClient = $this->getMockBuilder(SsmClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = ResultMockFactory::createFailing(GetParametersResult::class, 400, 'User: arn:aws:sts::123456:assumed-role/app-dev-us-east-1-lambdaRole/app-dev-hello is not authorized to perform: ssm:GetParameters on resource: arn:aws:ssm:us-east-1:123456:parameter/app/test because no identity-based policy allows the ssm:GetParameters action');
        $ssmClient->method('getParameters')
            ->willReturn($result);

        $expected = preg_quote("Bref was not able to resolve secrets contained in environment variables from SSM because of a permissions issue with the SSM API. Did you add IAM permissions in serverless.yml to allow Lambda to access SSM? (docs: https://bref.sh/docs/environment/variables.html#at-deployment-time).\nFull exception message:", '/');
        $this->expectExceptionMessageMatches("/$expected .+/");
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

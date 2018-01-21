# Framework for using AWS Lambda in PHP

Use cases:

- APIs
- GitHub webhooks
- Slack bots
- web tasks
- crons
- workers

## License

This project is under a proprietary license.

## TODO

- init: ask for the project name
- Deploy: auto-add `handler.js`
- Allow configuring the file name of the application (`lambda.php`)
- Handle errors/exceptions and logs
- Avoid using a temporary file for the output
- Test framework
- Move to serverless plugin entirely?
- Separate the deploy tool from the framework to minimize dependencies

## Setup

- Create an AWS account if you don't already have one
- Install [serverless](https://serverless.com): `npm install -g serverless`
- Setup your AWS credentials: [create an AWS access key](https://serverless.com/framework/docs/providers/aws/guide/credentials#creating-aws-access-keys) and either configure it [using an environment variable](https://serverless.com/framework/docs/providers/aws/guide/credentials#quick-setup) or [setup `aws-cli`](http://docs.aws.amazon.com/cli/latest/userguide/installing.html) (and run `aws configure`)

PHPLambda will then use AWS credentials and the serverless framework to deploy your application.

## Creating a lambda

```shell
$ composer require phplambda/phplambda
$ vendor/bin/phplambda init
```

Write a `lambda.php` file at the root of your project:

```php
<?php

require __DIR__.'/vendor/autoload.php';

$app = new \PhpLambda\Application;

$app->simpleHandler(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
$app->run();
```

If you want to keep things simple for now, simply use the `λ` shortcut :)

```php
<?php

require __DIR__.'/vendor/autoload.php';

λ(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
```

Watch out: if you want to setup an HTTP handler (e.g. for the webhook) you need to use an HTTP framework. This is described at the end of this page.

## Deployment

```shell
$ serverless deploy
```

## Invocation

By default lambdas are deployed with a webhook. You can trigger them by simply calling the webhook. If in doubt, the webhook can be retrieved using `serverless info`.

```shell
$ curl https://xxxxx.execute-api.xxxxx.amazonaws.com/dev/
```

Triggering a lambda manually:

```shell
# "main" is the name of the function created by default
# you can have several functions in the same projects
$ serverless invoke -f main
```

Triggering a lambda from another PHP application:

```php
$lambda = new \Aws\Lambda\LambdaClient([
    'version' => 'latest',
    'region' => 'us-east-1',
]);
$result = $lambda->invoke([
    'FunctionName' => '<function-name>',
    'InvocationType' => 'Event',
    'LogType' => 'None',
    'Payload' => json_encode([...]),
]);
$payload = json_decode($result->get('Payload')->getContents(), true);
```

## Deletion

```shell
$ serverless remove
```

## HTTP applications

PHPLambda provides bridges to use your HTTP framework and write an HTTP application. By default it supports any [PSR-15 request handler](https://github.com/http-interop/http-server-handler) implementation, thanks to PSR-7 it is easy to integrate most frameworks.

Here is an example using the [Slim](https://www.slimframework.com) framework to handle requests:

```php
<?php

use PhpLambda\Bridge\Slim\SlimAdapter;

require __DIR__.'/vendor/autoload.php';

$slim = new Slim\App;
$slim->get('/dev', function ($request, $response) {
    $response->getBody()->write('Hello world!');
    return $response;
});

$app = new \PhpLambda\Application;
$app->httpHandler(new SlimAdapter($slim));
$app->run();
```

Here is another example using [Symfony](https://symfony.com/):

```php
<?php

use PhpLambda\Bridge\Symfony\SymfonyAdapter;

require __DIR__.'/vendor/autoload.php';

$kernel = new \App\Kernel('prod', false);

$app = new \PhpLambda\Application;
$app->httpHandler(new SymfonyAdapter($kernel));
$app->cliHandler(new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel));
$app->run();
```

Things to note about the Symfony integration:

- the `terminate` event is run synchronously before the response is sent back (we are not in a fastcgi setup)
- you may need to customize the list of directories that will be packaged and deployed into the lambda (in `serverless.yml`)
- you need to define some environment variable for Symfony to configure itself on the lambda. To do this, edit the generated `serverless.yml` for non-sensitive configuration:
    ```yaml
    functions:
      main:
        ...
        # Add this section:
        environment:
          APP_ENV: 'prod'
          APP_DEBUG: '0'
    ```
    
    For sensitive information those should be defined in a more secure way, for example through AWS's console.
- since you will not be deploying `composer.json` into the lambda (because there is no reason to), you will need to explicitly define the project dir to Symfony by adding this method in your Kernel class:
    ```php
    public function getProjectDir()
    {
        return realpath(__DIR__.'/../');
    }
    ```
- the filesystem is readonly on lambdas except for `/tmp`, as such you need to customize the paths for caches and logs:
    ```php
    public function getCacheDir()
    {
        // When on the lambda only /tmp is writeable
        if (getenv('LAMBDA_TASK_ROOT') !== false) {
            return '/tmp/cache/'.$this->environment;
        }

        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }
    
    public function getLogDir()
    {
        // When on the lambda only /tmp is writeable
        if (getenv('LAMBDA_TASK_ROOT') !== false) {
            return '/tmp/log/';
        }

        return $this->getProjectDir().'/var/log';
    }
    ```
- you need to add build steps in `serverless.yaml`:
    ```yaml
    custom:
      phpbuild:
        - 'php bin/console cache:clear --env=prod --no-debug --no-warmup'
        - 'php bin/console cache:warmup --env=prod'
        - 'composer install --no-dev --optimize-autoloader'
    ```

PHPLambda provides a helper to preview the application locally, simply run:

```shell
$ php -S 127.0.0.1:8000 lambda.php
```

And open [http://localhost:8000](http://localhost:8000/).

Remember that you can also keep the `simpleHandler` so that your lambda handles both HTTP requests and direct invocations.

### Why is there a `/dev` prefix in the URLs on AWS Lambda

See [this StackOverflow question](https://stackoverflow.com/questions/46857335/how-to-remove-stage-from-urls-for-aws-lambda-functions-serverless-framework) for a more detailed answer. The short version is AWS requires a prefix containing the stage name (dev/prod).

If you use a custom domain for your application this prefix will disappear. If you don't, you need to write routes with this prefix in your framework.

## CLI applications

PHPLambda provides an abstraction to easily run CLI commands in lambdas. You can define a CLI application using [Symfony Console](https://symfony.com/doc/master/components/console.html) or [Silly](https://github.com/mnapoli/silly) (which extends and simplifies Symfony Console). Once the lambda is deployed you can then "invoke" the CLI commands in the lambda using `phplambda cli -- <command>`.

```php
<?php

require __DIR__.'/vendor/autoload.php';

$silly = new \Silly\Application;
$silly->command('hello [name]', function (string $name = 'World!', $output) {
    $output->writeln('Hello ' . $name);
});

$app = new \PhpLambda\Application;
$app->cliHandler($silly);
$app->run();
```

To run CLI commands in the lambda, simply run `phplambda cli` on your computer:

```shell
$ vendor/bin/phplambda cli
[…]
# Runs the CLI application without arguments and displays the help

$ vendor/bin/phplambda cli -- hello
Hello World!

$ vendor/bin/phplambda cli -- hello Bob
Hello Bob
```

As you can see, all arguments and options after `phplambda cli --` are forwarded to the CLI command running on lambda.

To test your CLI commands locally, simply run:

```shell
$ php lambda.php <commands and options>
```

## Build hooks

You can execute scripts when the lambda is being prepared:

```yaml
custom:
  phpbuild:
    - 'composer install --no-dev --optimize-autoloader'
```

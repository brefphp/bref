---
title: Timeouts
current_menu: timeouts
introduction: Configure and handle timeouts.
---

When a Lambda function times out, it is like the power to the computer is suddenly
just turned off. This does not give the application a chance to shutdown properly.
This often leaves you without any logs and the problem could be hard to fix.

Bref will throw an `LambdaTimeout` exception just before the Lambda actually times
out. This will allow your application to actually shutdown.

This feature is enabled automatically for the `php-xx` layer and the `console` layer.
The `php-xx-fpm` layer needs to opt-in by adding the following to `index.php`.

```php
if (isset($_SERVER['LAMBDA_TASK_ROOT'])) {
    \Bref\Timeout\Timeout::enable();
}
```

## Configuration

You may configure this behavior with the `BREF_TIMEOUT` environment variable. To
always trigger an exception after 10 seconds, set `BREF_TIMEOUT=10`. To disable
Bref throwing an exception use value `BREF_TIMEOUT=-1`. To automatically set the
timeout just a hair shorter than the Lambda timeout, use `BREF_TIMEOUT=0`.

## Catching the exception

If you are using a framework, then the framework is probably catching all exceptions
and displays an error page for the users. You may of course catch the exception
yourself:

```php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Bref\Context\Context;
use Bref\Timeout\LambdaTimeout;

class Handler implements \Bref\Event\Handler
{
    public function handle($event, Context $context)
    {
        try {
            $this->generateResponse();
        } catch (LambdaTimeout $e) {
            echo 'Oops, sorry. We spent too much time on this.';
        } catch (\Throwable $e) {
            echo 'Some unexpected error happened.';
        }
    }

    private function generateResponse()
    {
        $pi = // ...
        echo 'Pi is '.$pi;
    }
}

return new Handler();
```

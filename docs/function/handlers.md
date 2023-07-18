---
title: Typed PHP Lambda handlers
current_menu: typed-handlers
introduction: Handle AWS Lambda events using typed PHP classes.
previous:
    link: /docs/runtimes/function.html
    title: PHP functions on AWS Lambda
next:
    link: /docs/function/local-development.html
    title: Local development
---

Handling Lambda events via an anonymous function is the simplest approach:

```php
return function ($event) {
    return 'Hello ' . $event['name'];
};
```

However, Bref also provides classes specific to each Lambda event for a better development experience.

Here is an example using the base `Handler` class, that can handle events of any type:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Bref\Context\Context;

class Handler implements \Bref\Event\Handler
{
    public function handle($event, Context $context)
    {
        return 'Hello ' . $event['name'];
    }
}

return new Handler();
```

## Autoloading

As you can see in the example above, we define the class in the "handler" file, i.e. the same file where we `return new Handler()`.

But we can perfectly move that class to another directory, Composer will autoload it as usual:

```php
<?php // my-function.php

require __DIR__ . '/vendor/autoload.php';

// The class is stored in `src/` or `app/` and autoloaded by Composer
return new \MyApp\Handler();
```

What is important is to configure `serverless.yml` to use the file that returns the handler:

```yaml
# ...

functions:
    hello:
        handler: my-function.php # the file that returns the handler
```

All the examples in this page will mix the class and the `return` for simplicity.

## API Gateway HTTP events

**Reminder:** to create HTTP applications, it is possible to use the more traditional "[Bref for web apps](/docs/web-apps/index.md)" runtime, which runs with PHP-FPM.

That being said, it is possible to handle HTTP events from API Gateway with a simple PHP class, like other handlers detailed in this page.

Here is a full comparison between both approaches:

|                                                    | Bref for web apps                                                                                                       | HTTP handler class                                                                    |
|----------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------|
| What are the use cases?                            | To build websites, APIs, etc. This should be the **default approach** as it's compatible with mature PHP frameworks and tools. | Build a small website, API, webhook with very little code and no framework.           |
| Why does that solution exist?                      | For out-of-the-box compatibility with frameworks like Symfony and Laravel.                                                 | To match how other languages run in AWS Lambda, as recommended by AWS.                |
| How is it executed?                                | Using PHP-FPM.                                                                                                             | Using the PHP CLI.                                                                    |
| What does the routing (i.e. separate pages)?       | Your PHP framework (one Lambda receives all the URLs).                                                                     | API Gateway: we define one Lambda and one handler class per route.                    |
| How to read the request?                           | `$_GET`, `$_POST`, etc.                                                                                                        | The `$request` parameter (PSR-7 request).                                             |
| How to write a response?                           | `echo`, `header()` function, etc.                                                                                          | Returning a PSR-7 response from the handler class.                                    |
| How does it work?                                  | Bref turns an API Gateway event into a FastCGI (PHP-FPM) request.                                                          | Bref turns an API Gateway event into a PSR-7 request.                                 |
| Is each request handled in a separate PHP process? | Yes (that's how PHP-FPM works).                                                                                            | Yes (Bref explicitly replicates that to avoid surprises, but that can be customized). |

To create an HTTP handler class, Bref natively supports the [PSR-15](https://www.php-fig.org/psr/psr-15/#2-interfaces) and [PSR-7](https://www.php-fig.org/psr/psr-7/) standards:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getQueryParams()['name'] ?? 'world';

        return new Response(200, [], "Hello $name");
    }
}

return new HttpHandler();
```

Since a handler is a controller for a specific route, we can use API Gateway's routing to deploy multiple Lambda functions:

```yaml
functions:
    create-article:
        handler: create-article-handler.php
        runtime: php-81
        events:
            - httpApi: 'POST /articles'
    get-article:
        handler: get-article-handler.php
        runtime: php-81
        events:
            - httpApi: 'GET /articles/{id}'
```

Note that path parameters (e.g. `{id}` in the example above) are available as request attributes in the PSR-7 request:

```php
$id = $request->getAttribute('id');
```

[Full reference of HTTP events in `serverless.yml`](https://www.serverless.com/framework/docs/providers/aws/events/http-api/).

### Lambda event and context

The API Gateway event and Lambda context are available as attributes on the request:
```php
/** @var $event Bref\Event\Http\HttpRequestEvent */
$event = $request->getAttribute('lambda-event'); 

/** @var $context Bref\Context\Context */
$context = $request->getAttribute('lambda-context');
```

If you're looking for the request context array, for example when using a [Lambda authorizer](https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-lambda-authorizer.html#http-api-lambda-authorizer.payload-format-response):
```php
$requestContext = $request->getAttribute('lambda-event')->getRequestContext(); 
```

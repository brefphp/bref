---
title: Web applications on AWS Lambda
current_menu: web-apps
introduction: Learn how to run serverless HTTP applications with PHP on AWS Lambda using Bref.
previous:
    link: /docs/runtimes/
    title: PHP runtimes for AWS Lambda
next:
    link: /docs/websites.html
    title: Website assets
---

To run HTTP APIs and websites on AWS Lambda, Bref runs your code **using PHP-FPM**. That means PHP applications can run on Lambda just like on any other PHP hosting platform.

That's great: we can use our favorite framework as usual, like **Laravel or Symfony**.

If you are interested in the details, know that AWS Lambda can react to HTTP requests via [API Gateway](https://aws.amazon.com/api-gateway/). On Lambda, Bref forwards API Gateway requests to PHP-FPM via the FastCGI protocol (just like Apache or Nginx).

> Every code we deploy on AWS Lambda is called a "Function". Do not let this name confuse you: in this chapter, we do deploy HTTP **applications** in a Lambda Function.
>
> In the Lambda world, an HTTP application is a *function* that is called by a request and returns a response. Good news: this is exactly what our PHP applications do.

## Setup

Below is a minimal `serverless.yml` configuration to deploy an HTTP application:

```yaml
service: app
provider:
    name: aws
    runtime: provided.al2
plugins:
    - ./vendor/bref/bref
functions:
    app:
        handler: index.php
        runtime: php-81-fpm
        events:
            - httpApi: '*'
```

To create it automatically, run `vendor/bin/bref init` and select "Web application".

## Handler

The *handler* is the file that will be invoked when an HTTP request comes in.

It is the same file that is traditionally configured in Apache or Nginx. In Symfony and Laravel this is usually `public/index.php` but it can be anything.

```yaml
functions:
    app:
        handler: public/index.php
```

## Runtime

For web apps, the runtime to use is the **FPM** runtime (`php-81-fpm`):

```yaml
functions:
    app:
        runtime: php-81-fpm
```

To learn more check out [the runtimes documentation](/docs/runtimes/README.md).

## Routing

On AWS Lambda there is no Apache or Nginx. API Gateway acts as the webserver.

The simplest API Gateway configuration is to send all incoming requests to our application:

```yaml
        events:
            - httpApi: '*'
```

### Assets

Lambda and API Gateway are only used for executing code. Serving assets via PHP does not make sense as this would be a waste of resources and money.

Deploying a website and serving assets (e.g. CSS, JavaScript, images) is covered in [the "Website assets" documentation](/docs/websites.md).

In some cases however, you will need to serve images (or other assets) via PHP. One example would be if you served generated images via PHP. In those cases, you need to read the [Binary requests and responses](#binary-requests-and-responses) section below.

## Binary requests and responses

By default API Gateway **does not support binary HTTP requests or responses** like
images, PDF, binary files… To achieve this, you need to enable the option for binary
media types in `serverless.yml` as well as define the `BREF_BINARY_RESPONSES` environment
variable:

```yaml
provider:
    # ...
    apiGateway:
        binaryMediaTypes:
            - '*/*'
    environment:
        BREF_BINARY_RESPONSES: '1'
```

This will make API Gateway support binary file uploads and downloads, and Bref will
automatically encode responses to base64 (which is what API Gateway now expects).

Be aware that the max upload and download size is 6MB.
For larger files, use AWS S3.
An example is available in [Serverless Visually Explained](https://serverless-visually-explained.com/).

## Context access

### Lambda context

Lambda provides information about the invocation, function, and execution environment via the *lambda context*.

Bref exposes the Lambda context in the `$_SERVER['LAMBDA_INVOCATION_CONTEXT']` variable as a JSON-encoded string.
Here is an example to retrieve it:

```php
$lambdaContext = json_decode($_SERVER['LAMBDA_INVOCATION_CONTEXT'], true);
```

### Request context

API Gateway integrations can add information to the HTTP request via the *request context*.
This is the case, for example, when using AWS Cognito authentication on API Gateway.

Bref exposes the request context in the `$_SERVER['LAMBDA_REQUEST_CONTEXT']` variable as a JSON-encoded string.
Here is an example to retrieve it:

```php
$requestContext = json_decode($_SERVER['LAMBDA_REQUEST_CONTEXT'], true);
```

## Cold starts

AWS Lambda automatically destroys Lambda containers that have been unused for 10 minutes. Warming up a new container can take some time, especially if your application is large. This delay is called [cold start](https://mikhail.io/serverless/coldstarts/aws/).

To mitigate cold starts for HTTP applications, you can periodically send an event to your Lambda including a `{warmer: true}` key. Bref recognizes this event and immediately responds with a `Status: 100` without executing your code.

You can set up such events using AWS CloudWatch ([read this article for more details](https://www.jeremydaly.com/lambda-warmer-optimize-aws-lambda-function-cold-starts/)):

```yaml
        events:
            - httpApi: '*'
            - schedule:
                rate: rate(5 minutes)
                input:
                    warmer: true
```

You can learn more how AWS Lambda scales and runs in the [Serverless Visually Explained](https://serverless-visually-explained.com/) course.

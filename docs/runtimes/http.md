---
title: HTTP applications
current_menu: http-applications
introduction: Learn how to run serverless HTTP applications with PHP on AWS Lambda using Bref.
previous:
    link: /docs/runtimes/function.html
    title: PHP functions
next:
    link: /docs/runtimes/console.html
    title: Console applications
---

It is possible to run HTTP APIs and websites on AWS Lambda.

To do that, Bref runs your code on AWS Lambda **using PHP-FPM**. That means HTTP applications can run on AWS Lambda just like on any other PHP hosting platform.

If you are interested in the details, know that AWS Lambda can react to HTTP requests via [API Gateway](https://aws.amazon.com/api-gateway/).

> Every code we deploy on AWS Lambda is called a "Function". Do not let this name confuse you: we do deploy HTTP **applications** in a Lambda Function.
>
> In the Lambda world, an HTTP application is a *function* that is called by a request and returns a response. Good news: this is exactly what our PHP applications do.

Below is a minimal `serverless.yml` to deploy HTTP applications. To create it automatically run `vendor/bin/bref init`.

```yaml
service: app
provider:
    name: aws
    runtime: provided
plugins:
    - ./vendor/bref/bref
functions:
    website:
        handler: index.php
        layers:
            - ${bref:layer.php-73-fpm}
        # This section contains the URL routing configuration of API Gateway
        events:
            -   http: 'ANY /'
            -   http: 'ANY /{proxy+}'
```

## Handler

The *handler* is the file that will be invoked when an HTTP request comes in.

It is the same file that is traditionally configured in Apache or Nginx. In Symfony and Laravel this is usually `public/index.php` but it can be anything.

```yaml
functions:
    website:
        handler: public/index.php
```

## Runtime

The runtime (aka layer) is different than with [PHP functions](function.md). Instead of `php-73` it should be `php-73-fpm` because we are using PHP-FPM.

```yaml
functions:
    website:
        layers:
            - ${bref:layer.php-73-fpm}
```

To learn more check out [the runtimes documentation](/docs/runtimes/README.md).

## The /dev/ prefix

API Gateway works with "stages": a stage is an environment (e.g. dev, test, prod).

This is why applications are deployed with URLs ending with the stage name, for example `https://hc4rcprbe2.execute-api.us-east-1.amazonaws.com/dev/`. See [this StackOverflow question](https://stackoverflow.com/questions/46857335/how-to-remove-stage-from-urls-for-aws-lambda-functions-serverless-framework) for more information.

If you [setup a custom domain for your application](/docs/environment/custom-domains.md) this prefix will disappear. If you don't, you need to take this prefix into account in your application routes in your PHP framework.

> If you haven't set up a custom domain yet and you want to get rid of the `/dev` prefix, you can try the [bref.dev](https://bref.dev) service. Run the `vendor/bin/bref bref.dev` command. Remember that this service is currently in beta and can change in the future.

## Routing

On AWS Lambda there is no Apache or Nginx. API Gateway acts as the webserver.

To configure HTTP routing we must configure API Gateway using `serverless.yml`.

### Catch-all

The simplest configuration is to catch all incoming requests and send them to PHP. With API Gateway we must define 2 patterns:

- the `/` root URL
- the `/*` catch-all URL (which does not catch `/`)

Here is an example of such configuration:

```yaml
        events:
            -   http: 'ANY /'
            -   http: 'ANY /{proxy+}'
```

### Advanced routing

API Gateway provides a routing system that lets us define routes that match specific URLs and HTTP methods. For example:

```yaml
        events:
            -   http: 'POST /articles'
            -   http: 'GET /articles/{id}'
```

Use `{foo}` as a placeholder for a parameter and `{foo+}` as a parameter that matches everything, including sub-folders.

### Assets

Lambda and API Gateway are only used for executing code. Serving assets via PHP does not make sense as this would be a waste of resources and money.

Deploying a website and serving assets (e.g. CSS, JavaScript, images) is covered in [the "Websites" documentation](/docs/websites.md).

In some cases however, you will need to serve images (or other assets) via PHP. One example would be if you served generated images via PHP. In those cases, you need to read the [Binary response](#binary-responses) section below.

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
        BREF_BINARY_RESPONSES: 1
```

This will make API Gateway support binary file uploads and downloads, and Bref will 
automatically encode responses to base64 (which is what API Gateway now expects).

## Cold starts

AWS Lambda automatically destroys Lambda containers that have been unused for 10 to 60 minutes. Warming up a new container can take some time, especially if your package is large or if your Lambda is connected to a VPC. This delay is called [cold start](https://mikhail.io/serverless/coldstarts/aws/).

To mitigate cold starts for HTTP applications, you can periodically send an event to your Lambda including a `{warmer: true}` key. Bref recognizes this event and immediately responds with a `{status: 100}` without executing your code.

You can generate automatically such events using AWS CloudWatch ([read this article for more details](https://www.jeremydaly.com/lambda-warmer-optimize-aws-lambda-function-cold-starts/)). For example :

```yaml
        events:
            -   http: 'ANY /'
            -   http: 'ANY /{proxy+}'
            - schedule:
                rate: rate(5 minutes)
                input:
                    warmer: true
```

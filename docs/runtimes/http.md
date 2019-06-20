---
title: HTTP applications
currentMenu: http-applications
introduction: Learn how to run serverless HTTP applications with PHP on AWS Lambda using Bref.
previous:
    link: /docs/runtimes/function.html
    title: PHP functions
next:
    link: /docs/runtimes/console.html
    title: Console applications
---

Bref uses PHP-FPM to run HTTP applications on AWS Lambda, just like any PHP hosting solution.

Below is a minimal `serverless.yml` to deploy HTTP applications. To create it automatically run `vendor/bin/bref init`.

```yaml
service: app
provider:
    name: aws
    runtime: provided
functions:
    hello:
        handler: index.php
        layers:
            - 'arn:aws:lambda:<region>:209497400698:layer:php-73-fpm:<version>'
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
    hello:
        handler: public/index.php
```

## Runtime

The runtime (aka layer) is different than with [PHP functions](function.md). Instead of `php-73` it should be `php-73-fpm` because we are using PHP-FPM.

```yaml
functions:
    hello:
        layers:
            - 'arn:aws:lambda:<region>:209497400698:layer:php-73-fpm:<version>'
```

To learn more check out [the runtimes documentation](/docs/runtimes/README.md).

## The /dev/ prefix

API Gateway works with "stages": a stage is an environment (e.g. dev, test, prod).

This is why applications are deployed with URLs ending with the stage name, for example `https://hc4rcprbe2.execute-api.us-east-1.amazonaws.com/dev/`. See [this StackOverflow question](https://stackoverflow.com/questions/46857335/how-to-remove-stage-from-urls-for-aws-lambda-functions-serverless-framework) for more information.

If you [setup a custom domain for your application](/docs/environment/custom-domains.md) this prefix will disappear. If you don't, you need to take this prefix into account in your application routes in your PHP framework.

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

Deploying a website and serving assets (e.g. CSS, JavaScript, images) will be covered later in another article.

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

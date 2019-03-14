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

AWS Lambda can respond to HTTP requests via [API Gateway](https://aws.amazon.com/api-gateway/).

Instead of dealing with API Gateway's custom request and response formats, **Bref uses PHP-FPM to run PHP**.

That means HTTP applications can run on AWS Lambda just like on any other PHP hosting platform.

Below is a minimal `template.yaml` to deploy HTTP applications. To create it automatically run `vendor/bin/bref init`.

```yaml
AWSTemplateFormatVersion: '2010-09-09'
Transform: AWS::Serverless-2016-10-31
Resources:
    MyFunction:
        Type: AWS::Serverless::Function
        Properties:
            FunctionName: 'my-function'
            CodeUri: .
            Handler: public/index.php
            Runtime: provided
            Layers:
                - 'arn:aws:lambda:<region>:209497400698:layer:php-73-fpm:<version>'
            # This section contains the URL routing configuration of API Gateway
            Events:
                HttpRoot:
                    Type: Api
                    Properties:
                        Path: /
                        Method: ANY
                HttpSubPaths:
                    Type: Api
                    Properties:
                        Path: /{proxy+}
                        Method: ANY
# This lets us retrieve the app's URL in the "Outputs" tab in CloudFormation
Outputs:
    DemoHttpApi:
        Description: 'API Gateway URL for our function'
        Value: !Sub 'https://${ServerlessRestApi}.execute-api.${AWS::Region}.amazonaws.com/Prod/'
```

## Handler

The *handler* is the file that will be invoked when an HTTP request comes in.

It is the same file that is traditionally configured in Apache or Nginx. In Symfony and Laravel this is usually `public/index.php` but it can be anything.

```yaml
Resources:
    MyFunction:
        Properties:
            Handler: public/index.php
```

## Runtime

The runtime (aka layer) is different than with [PHP functions](function.md). Instead of `php-73` it should be `php-73-fpm` because we are using PHP-FPM.

```yaml
Resources:
    MyFunction:
        Properties:
            Runtime: provided
            Layers:
                - 'arn:aws:lambda:<region>:209497400698:layer:php-73-fpm:<version>'
```

To learn more check out [the runtimes documentation](/docs/runtimes/README.md).

## The /Prod/ prefix

API Gateway works with "stages": a stage is an environment (e.g. dev, test, prod).

This is why applications are deployed with URLs ending with the stage name, for example `https://hc4rcprbe2.execute-api.us-east-1.amazonaws.com/Prod/`. See [this StackOverflow question](https://stackoverflow.com/questions/46857335/how-to-remove-stage-from-urls-for-aws-lambda-functions-serverless-framework) for more information.

If you [setup a custom domain for your application](/docs/environment/custom-domains.md) this prefix will disappear. If you don't, you need to take this prefix into account in your application routes in your PHP framework.

## Routing

On AWS Lambda there is no Apache or Nginx. API Gateway acts as the webserver.

To configure HTTP routing we must configure API Gateway using SAM.

### Catch-all

The simplest configuration is to catch all incoming requests and send them to PHP. With API Gateway we must define 2 patterns:

- the `/` root URL
- the `/*` catch-all URL (does not catch `/`)

Here is an example of such configuration:

```yaml
            Events:
                HttpRoot:
                    Type: Api
                    Properties:
                        Path: /
                        Method: ANY
                HttpSubPaths:
                    Type: Api
                    Properties:
                        Path: /{proxy+}
                        Method: ANY
```

### Advanced routing

API Gateway provides a routing system that lets us define routes that match specific URLs and HTTP methods. For example:

```yaml
            Events:
                CreateArticle:
                    Type: Api
                    Properties:
                        Path: /articles
                        Method: POST
                GetArticle:
                    Type: Api
                    Properties:
                        Path: /articles/{id}
                        Method: GET
```

Use `{foo}` as a placeholder for a parameter and `{foo+}` as a parameter that matches everything, including sub-folders.

### Assets

Lambda and API Gateway are only used for executing code. Serving assets via PHP does not make sense as this would be a waste of resources and money.

Deploying a website and serving assets (e.g. CSS, JavaScript, images) will be covered later in another article.

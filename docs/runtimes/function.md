# PHP functions

The simplest way to write a lambda is to write one in the form of a PHP function:

```php
<?php

require __DIR__.'/vendor/autoload.php';

lambda(function (array $event) {
    return 'Hello ' . ($event['name'] ?? 'world');
});
```

This form is very similar to lambdas written in other languages, [for example JavaScript](https://docs.aws.amazon.com/lambda/latest/dg/nodejs-prog-model-handler.html):

```javascript
exports.myHandler = async function (event, context) {
   return "Hello " + event.name;
}
```

## The function

A function can be defined by passing a *callable* to the `lambda()` PHP function. The callable can be an anonymous function or [any kind of callable supported by PHP](http://php.net/manual/en/language.types.callable.php).

```php
<?php

require __DIR__.'/vendor/autoload.php';

lambda(function (array $event) {
    return /* response */;
});
```

The function:

- takes an `$event` parameter which contains data from the event that triggered the function ([list of examples here](https://docs.aws.amazon.com/lambda/latest/dg/eventsources.html))
- can optionally return a response: the response must be serializable to JSON

There must be only one function defined per PHP file.

## SAM configuration

The minimal `template.yaml` you will need to create should be:

```yaml
AWSTemplateFormatVersion: '2010-09-09'
Transform: 'AWS::Serverless-2016-10-31'
Resources:
    MyFunction:
        Type: AWS::Serverless::Function
        Properties:
            FunctionName: 'my-function'
            CodeUri: .
            Handler: index.php # the name of your PHP file
            Runtime: provided
            Layers:
                - 'arn:aws:lambda:<region>:416566615250:layer:php-72:<version>'
```

The runtime to use is `php`. To learn more, check out [the runtimes documentation](/docs/runtimes/README.md).

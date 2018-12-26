This directory contains the scripts that create and publish the AWS Lambda runtimes for PHP.

## Runtimes

- default: contains the PHP CLI binary, useful for applications that are not related to HTTP
- fpm: contains PHP-FPM (and PHP CLI), useful for HTTP applications
- console: layer that should be used on top of `default` to run console commands (Symfony Console or Laravel Artisan)
- loop: experimental mode

To use a runtime you need to import the corresponding layer into your Lambda. For example using AWS SAM:

```yaml
Resources:
    DemoFunction:
        Type: AWS::Serverless::Function
        Properties:
            [...]
            Layers:
                - '<the layer ARN here>'
```

Each layer's ARN can be composed through the following pattern:

```
arn:aws:lambda:<region>:416566615250:layer:php-72:<version>
arn:aws:lambda:<region>:416566615250:layer:php-72-fpm:<version>
arn:aws:lambda:<region>:416566615250:layer:php-72-console:<version>
arn:aws:lambda:<region>:416566615250:layer:php-72-loop:<version>
```

TODO: display the latest version for each layer.

Supported regions:

- `us-east-2`

TODO: publish on multiple regions.

### Default runtime for non-HTTP lambdas

When writing lambdas that are not invoked via API Gateway (i.e. not an HTTP application) you will want to use this runtime.

On every Lambda invocation your PHP script will be invoked.

### PHP-FPM for HTTP lambdas

When writing lambdas that are invoked via API Gateway (i.e. HTTP applications) you will want to use this runtime.

When the lambda boots (cold start) it starts up PHP-FPM. On every invocation (HTTP request) Bref will forward the event to PHP-FPM which will invoke your script.

This lets your application run just like with Apache and PHP-FPM.

## `php.ini`

Bref's default `php.ini` is `/opt/php.ini` in your lambda.

You can create extra configuration files to customize its options:

- create a subdirectory in your project (e.g. `php/`)
- create a `php.ini` file inside that directory (the name of the file does not matter)
- define the `PHP_INI_SCAN_DIR` environment variable to point to that new directory

  The `PHP_INI_SCAN_DIR` must contain an absolute path. Since your code is placed in `/var/task` on AWS Lambda the environment variable should contain something like `/var/task/php`.

Here is an example of how to define it in your SAM template:

```yaml
Resources:
    MyFunction:
        Type: AWS::Serverless::Function
        Properties:
            [...]
            Environment:
                Variables:
                    PHP_INI_SCAN_DIR: '/var/task/php'
```

## Extensions

Some extensions are bundled by default in the PHP layer. Bref intends to include the most common extensions by default. If a major PHP extension is missing please send a pull request to add it.

Any other extension can be added through extra layers by putting them in the `/opt/php/extensions` directory and loading them via `php.ini`.

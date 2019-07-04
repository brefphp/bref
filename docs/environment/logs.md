---
title: Logs
currentMenu: logs
introduction: Learn how to write and read PHP logs on AWS Lambda using Bref.
---

As explained in the [storage documentation](storage.md), the filesystem on AWS Lambda is:

- read-only, except for `/tmp`
- not shared between lambda instances
- not persistent

Because of that, logs should not be stored on disk.

## CloudWatch

The simplest solution is to push logs to AWS CloudWatch, AWS' service for logs.

### PHP errors and warnings

By default, all PHP errors, warnings and notices emitted by PHP will be forwarded into CloudWatch.

That means that you don't have to configure anything to log errors, warnings or uncaught exceptions.

### Writing logs

Your application can write logs to CloudWatch:

- in a [PHP function](/docs/runtimes/function.md): write logs to `stdout` (using `echo` for example) or `stderr`
- in a [HTTP application](/docs/runtimes/http.md): write logs to `stderr`

For example with [Monolog](https://github.com/Seldaek/monolog):

```php
$log = new Monolog\Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));

$log->warning('This is a warning!');
```

For simple needs, you can replace Monolog with [Bref's logger](https://github.com/brefphp/logger), a PSR-3 logger designed for AWS Lambda:

```php
$logger = new \Bref\Logger\StderrLogger();

$log->warning('This is a warning!');
```

### Reading logs

To read logs, either open the [CloudWatch console](https://console.aws.amazon.com/cloudwatch/home#logs:) or use SAM in the CLI:

```bash
sam logs --name <function-name>

# Tail logs:
sam logs --name <function-name> --tail
```

## Advanced use cases

If you have more specific needs you can send logs to other services, for example Logstash, Papertrail, or Loggly.

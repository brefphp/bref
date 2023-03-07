---
title: Logs
current_menu: logs
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

- [Bref for web apps](/docs/runtimes/http.md): write logs to `stderr`
- [Bref for event-driven functions](/docs/runtimes/function.md): write logs to `stdout` (using `echo` for example) or `stderr`

For example with [Monolog](https://github.com/Seldaek/monolog):

```php
$log = new Monolog\Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));

$log->warning('This is a warning!');
```

For simple needs, you can replace Monolog with [Bref's logger](https://github.com/brefphp/logger), a PSR-3 logger designed for AWS Lambda:

```php
$log = new \Bref\Logger\StderrLogger();

$log->warning('This is a warning!');
```

### Reading logs

To read logs, either open the [CloudWatch console](https://console.aws.amazon.com/cloudwatch/home#logs:) or the [Bref Dashboard](https://dashboard.bref.sh/?ref=bref).

You can also use `serverless logs` to view them in the CLI:

```bash
serverless logs -f <function-name>

# Tail logs:
serverless logs -f <function-name> --tail
```

---
layout: home
---

Bref helps you build serverless PHP applications.

[![Build Status](https://travis-ci.com/mnapoli/bref.svg?branch=master)](https://travis-ci.com/mnapoli/bref)
[![Latest Version](https://img.shields.io/github/release/mnapoli/bref.svg?style=flat-square)](https://packagist.org/packages/mnapoli/bref)
[![PrettyCI Status](https://hc4rcprbe1.execute-api.eu-west-1.amazonaws.com/dev?name=mnapoli/bref)](https://prettyci.com/)
[![Monthly Downloads](https://img.shields.io/packagist/dm/mnapoli/bref.svg)](https://packagist.org/packages/mnapoli/bref/stats)

Bref brings support for PHP on serverless providers (AWS Lambda only for now) but also goes beyond that: it provides a deployment process tailored for PHP as well as the ability to create:

- classic lambdas (a function taking an "event" and returning a result)
- HTTP applications written with popular PHP frameworks
- CLI applications

It is currently in beta version and will get more and more complete with time, but it is used in production successfully. Contributions are welcome!

If you want to understand what serverless is please read the [Serverless and PHP: introducing Bref](http://mnapoli.fr/serverless-php/) article.

Use case examples:

- APIs
- workers
- crons/batch processes
- GitHub webhooks
- Slack bots

Interested about performances? [Head over here](https://github.com/mnapoli/bref-benchmark) for a benchmark.

## Logging

### Writing logs

The filesystem on lambdas is read-only (except for the `/tmp` folder). You should not try to write application logs to disk.

The easiest solution is to push logs to AWS Cloudwatch (Amazon's solution for logs). Bref (and AWS Lambda) will send to Cloudwatch anything you write on `stdout` (using `echo` for example) or `stderr`. If you are using Monolog this means you will need to configure Monolog to write to the output (contribution welcome: clarify with an example).

If you have more specific needs you can of course push logs to anything, for example Logstash, Papertrail, Loggly, etc.

### Reading logs

You can read the AWS Cloudwatch logs in the AWS console or via the CLI:

```shell
vendor/bin/bref logs
```

If you want to tail the logs:

```shell
vendor/bin/bref logs --tail
```

## Projects using Bref

Here are projects using Bref, feel free to add yours in a pull request:

- [prettyci.com](https://prettyci.com/)
- [returntrue.win](https://returntrue.win/)

---
title: Bref 2.0 is released 🎉
subTitle: Celebrating 10 billion executions per month
layout: news-article
articleDate: March 2023
author: Matthieu Napoli
authorGithub: mnapoli
---

The work on what would be Bref 2.0 started in October 2021, about 1.5 year ago. We went through many different strategies, experiments, rewrites, over **700 commits** to finally land with the stable release.

So far, Bref has been installed more than 2 million times and powers more than **10 billion Lambda executions** (aka requests) every month.

That's [1 in every 1000 AWS Lambda executions](https://twitter.com/matthieunapoli/status/1603032544424894464)!

Today, we celebrate these achievements, the ongoing work and **the release of Bref 2.0** 🎉

Let's dive in what's new in v2.

## Bref 2.0

Here's a summary, we'll dive in the details below:

- Simpler `serverless.yml` configuration for setting up PHP.
- ARM/Graviton support (faster processors with lower Lambda costs).
- Faster deployments by default.
- `vendor/bin/bref cli` becomes much simpler.
- Automatically load secrets in environment variables at runtime.
- Simpler `docker-compose.yml` for local development ([brefphp/aws-lambda-layers#38](https://github.com/brefphp/aws-lambda-layers/pull/38)).
- [PHP constructs for AWS CDK support](https://github.com/brefphp/constructs).
- The internals (the scripts that build the runtime) have been rewritten at least 4 times ([just look at the number of commits on the v2 runtimes…](https://github.com/brefphp/aws-lambda-layers)) but they are much better now: they are now tested, we understand all the code, we optimized their size, we've made the builds as fast as possible, and contributions and maintenance as easy as possible.

What did we break? **Nothing major**, the upgrade should be smooth. Here are the details:

- PHP 8.0+ is now required (7.4 support is dropped).
- Serverless Framework v3 is now required (2.x is obsolete). Run `serverless --version` to check.
- The `vendor/bin/bref` commands have been moved to the `serverless` CLI (detailed below).

## Simpler runtime configuration

Bref 2.0 lets us configure the runtime and PHP version in a much simpler way in `serverless.yml` ([#1394](https://github.com/brefphp/bref/pull/1394)). Here's an example below.

Note: **All of these new changes are optional**, you can keep using the Bref v1 syntax as it still works (no breaking changes).

Before (Bref v1 syntax):

```yaml
provider:
    name: aws
    runtime: provided.al2
functions:
    api:
        handler: public/index.php
        # ...
        layers:
            - ${bref:layer.php-81-fpm}
```

After (Bref v2 syntax):

```yaml
provider:
    name: aws
functions:
    api:
        handler: public/index.php
        # ...
        runtime: php-81-fpm
```

As you can see, we no longer have to set `runtime: provided.al2` and add the Bref layers. We can now directly set a PHP runtime (`php-81`, `php-81-fpm`, `php-81-console`) and Bref will turn this runtime configuration into the proper layer configuration.

This works for all the Bref runtimes ([FPM](https://bref.sh/docs/runtimes/http.html), [function](https://bref.sh/docs/runtimes/function.html) and [console](https://bref.sh/docs/runtimes/console.html)) and all supported PHP versions (`80`, `81`, and `82` at the moment). Here's a recap:

```yaml
# FPM runtime (web apps)
runtime: provided.al2
layers:
    - ${bref:layer.php-81-fpm}
# becomes:
runtime: php-81-fpm

# Function runtime
runtime: provided.al2
layers:
    - ${bref:layer.php-81}
# becomes:
runtime: php-81

# Console runtime
runtime: provided.al2
layers:
    - ${bref:layer.php-81}
    - ${bref:layer.console}
# becomes:
runtime: php-81-console
```

All the Bref documentation has also been updated to reflect these changes.

## ARM/Graviton support

Since 2021, it is possible to deploy Lambda functions running on ARM processors (called Graviton) instead of Intel x86 processors. However Bref did not support that.

These processors usually run applications faster ([here's an example](https://twitter.com/matthieunapoli/status/1605583651659345921)), and ARM functions [cost 20% less](https://aws.amazon.com/lambda/pricing/).

With Bref v2, we can deploy on ARM by setting the `architecture` field to `arm64`:

```yaml
provider:
    # ...
    architecture: arm64
functions:
    # ...
```

The `architecture: arm64` field can also be set [in each function individually](https://www.serverless.com/framework/docs/providers/aws/guide/functions#instruction-set-architecture).

**Warning:** the example above uses the new `runtime` syntax introduced above. If you set `layers` instead, you will need to update them to reference ARM layers:

```yaml
provider:
    # ...
    architecture: arm64
functions:
    api:
        # ...
        layers:
            # Add the `-arm` prefix in layers 👇
            - ${bref:layer.arm-php-81-fpm}
```

## Faster deployments

There is a `serverless.yml` option [to enable faster deployments](https://www.serverless.com/framework/docs/providers/aws/guide/deploying#deployment-method):

```yaml
provider:
    # ...
    deploymentMethod: direct
```

In Bref v2, this option will be enabled by default ([#1395](https://github.com/brefphp/bref/pull/1395)). If the option was already set in your `serverless.yml`, you can remove it (or leave it). If it wasn't, your deployments should be about twice faster.

## Simpler CLI commands

Using Bref means using 2 different CLIs:

- `vendor/bin/bref`
- `serverless`

With Bref v2, all commands (except `vendor/bin/bref init`) have been moved to the `serverless` CLI ([#1303](https://github.com/brefphp/bref/pull/1303)). Besides reducing confusion, integrating in the `serverless` CLI lets us re-use the same AWS credentials, region, stack names, function names, etc. It makes the commands simpler.

Here are the commands that have changed:

- `vendor/bin/bref cli` is replaced by the simpler `serverless bref:cli`.
 
  For example:
  
  ```bash
  vendor/bin/bref cli mystack-dev-artisan --region=eu-west-1 -- migrate --force
  # becomes:
  serverless bref:cli --args="migrate --force"
  ```
  
  No need to provide the function name or the region anymore. Read [the Console documentation](../runtimes/console.md#usage) to learn more. You will also find alternatives if you don't use the `serverless` CLI.

- `vendor/bin/bref local` is replaced by the simpler `serverless bref:local`.

  For example:

  ```bash
  vendor/bin/bref local --handler=my-handler.php
  # becomes:
  serverless bref:local -f hello
  ```
  
  No need to provide the handler file name anymore, we directly use the function name. The new `serverless bref:local` command has similar arguments as `serverless invoke`.

  Read [the Local Development documentation](../function/local-development.md) to learn more. You will also find alternatives if you don't use the `serverless` CLI.

- `vendor/bin/bref layers` is replaced by the simpler `serverless layers`.

  Layer versions are also available at [runtimes.bref.sh](https://runtimes.bref.sh/) if you don't use the `serverless` CLI.

These changes allowed us to simplify the commands (automatically use the AWS region, credentials and stage from the `serverless` CLI). It also allowed us to remove the biggest `bref/bref` Composer dependencies and make the package much lighter.

## Automatically load secrets at runtime

Bref v1 lets you inject secrets (API keys, DB passwords, etc.) stored in SSM into environment variables **at deployment time**:

```yaml
provider:
    # ...
    environment:
        GITHUB_TOKEN: ${ssm:/my-app/github-token}
```

This solution relies on `serverless.yml` variables (`${ssm:xxx}`) and works well, however the drawbacks are:

- The secret value will be retrieved on `serverless deploy` and set in plain text in the environment variable.
- The user that runs `serverless deploy` must have permissions to retrieve the secret value.

In Bref v2, you can have these secrets injected **at runtime** via a new syntax ([#1376](https://github.com/brefphp/bref/pull/1376)):

```yaml
provider:
    # ...
    environment:
        # Different syntax that does NOT start with `$`
        GITHUB_TOKEN: bref-ssm:/my-app/github-token
```

In the example above, `GITHUB_TOKEN` will be deployed with the string `bref-ssm:/my-app/github-token` (i.e. it doesn't contain the secret). When Lambda starts, Bref will automatically retrieve the secret and **replace** the environment variable value.

This offers a more secure solution for teams that prefer to keep secrets as tight as possible.

Read more about this new feature and secrets in general in the [Secrets documentation](../environment/variables.md#secrets).

## Thanks

A huge thanks to the [134 Bref contributors](https://github.com/brefphp/bref/graphs/contributors), to the community for supporting the project, and to those sponsoring the development:

- [Null](https://null.tc/)
- [Laravel](https://laravel.com/)
- [JetBrains](https://www.jetbrains.com/)

and [many others](https://github.com/sponsors/mnapoli#sponsors). Thank you all!

## That's it!

Hope you enjoy it!

You can also join the community [in Slack](/docs/community.md), post details about your project in [Built with Bref](https://github.com/brefphp/bref/issues/267), or share your experience online.

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
    <a href="/docs/" class="rounded-md shadow px-8 py-8 border text-center font-bold hover:bg-gray-100">What is Bref and serverless?</a>
    <a href="/docs/first-steps.html" class="rounded-md shadow px-8 py-8 border text-center font-bold hover:bg-gray-100">Get started with Bref</a>
</div>

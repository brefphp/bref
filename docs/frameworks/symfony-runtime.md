---
title: Serverless applications with Symfony Runtime
current_menu: symfony-runtime
introduction: Learn how to deploy serverless applications on AWS Lambda using Bref and Symfony Runtime component.
---

The Symfony Runtime runtime component was release with Symfony 5.3. All new Symfony
applications are using that component as default, but the component can also be
used with non-Symfony applications.

This guide helps you run any PHP applications on AWS Lambda using Bref and the Symfony
Runtime component.

## Setup

First, **follow the [Installation guide](../installation.md)** to create an AWS
account and install the necessary tools.

Next, in an any project using the [Symfony Runtime](https://symfony.com/doc/current/components/runtime.html),
install the Bref Runtime package.

```
composer require runtime/bref
```

If you are using [Symfony Flex](https://flex.symfony.com/), it will automatically run
the [runtime/bref recipe](https://github.com/symfony/recipes/tree/master/runtime/bref/0.2)
which will perform the following tasks:

- Create a `serverless.yml` configuration file.
- Add the `.serverless` folder to the `.gitignore` file.
- Add a `bootstrap` file in the project root.

> Otherwise, you can create the files yourself at the root of your project.
Take a look at the [serverless.yml](https://github.com/symfony/recipes/tree/master/runtime/bref/0.2/serverless.yaml)
and [bootstrap](https://github.com/symfony/recipes/tree/master/runtime/bref/0.2/bootstrap)
provided by the recipe.

You may still have a few modifications to do make your application compatible
with AWS Lambda. Since [the filesystem is readonly](/docs/environment/storage.md)
except for `/tmp` we need to customize where to store the cache, logs etc.

Take a look at the [Laravel](laravel.md) or [Symfony](symfony.md) setup guides how
they solve the problem.

## Deploy

The application is now ready to be deployed. Follow [the deployment guide](/docs/deploy.md).

---
title: First steps
currentMenu: first-steps
introduction: First steps to discover Bref and deploy your first PHP function on AWS Lambda.
previous:
    link: /docs/installation.html
    title: Installation
next:
    link: /docs/runtimes/
    title: What are runtimes?
---

This guide will help you deploy your first PHP function on AWS Lambda.

Before getting started make sure you have [installed Bref and the required tools](installation.md) first.

## Initializing the project

Starting in an empty directory, install Bref using Composer:

```
composer require bref/bref
```

Then let's start by initializing the project by running:

```
vendor/bin/bref init
```

Accept all the defaults by pressing "Enter" for each question. Congratulations, you have just created your first [PHP function](/docs/runtimes/function.md).

> If you are not too familiar with AWS Lambda you need to understand that a "function" is the simplest form of code that can be deployed on AWS Lambda. It is **not** a web application: it must be invoked via AWS tools.
>
> You will read in the next guides how to deploy web applications using the [HTTP runtime](/docs/runtimes/http.md). We want to start with something simpler right now!

The following files have been created in your project:

- `index.php` contains the function code
- `serverless.yml` contains the configuration for deploying on AWS

## Editing the code

You are free to edit the code in `index.php`, you must however keep the call to the `lambda()` function:

```php
require __DIR__.'/vendor/autoload.php';

lambda(function (array $event) {
    // Do anything you want here
    // For example:
    return 'Hello ' . ($event['name'] ?? 'world');
});
```

The `lambda()` function is the internal Bref function that makes sure your code is executed whenever the lambda is invoked.

You can use classes and functions as well: Composer and its autoloader will work just like any PHP application.

## Deployment

To learn how to deploy your first function head over the [Deployment guide](deploy.md).

## What's next?

Now that you have deployed a simple PHP function you can [learn more about runtimes](/docs/runtimes/). That will help you deploy HTTP and console applications.

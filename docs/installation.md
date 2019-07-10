---
title: Installation
currentMenu: installation
introduction: How to install Bref and the required tools.
previous:
    link: /docs/
    title: What is Bref and serverless?
next:
    link: /docs/first-steps.html
    title: First steps
---

To set up Bref correctly please complete all the sections below.

## AWS account

You will need an AWS account. To create one, go on [aws.amazon.com](https://aws.amazon.com/) and click *Sign up*.

AWS has a generous free tier that will usually allow you to deploy your first test applications for free.

## Serverless

Bref relies on the [Serverless framework](https://serverless.com/) and AWS access keys to deploy applications. You will need to:

- install the `serverless` command ([more details here](https://serverless.com/framework/docs/providers/aws/guide/quick-start/)):

    ```bash
    npm install -g serverless
    ```

- [create AWS access keys](/docs/installation/aws-keys.md)

- setup those keys by running:

    ```bash
    serverless config credentials --provider aws --key <key> --secret <secret>
    ```

    If you already use the `aws` CLI command, or if you want to use environment variables instead (for example for a shared server like a CI) you can [read the full guide](https://serverless.com/framework/docs/providers/aws/guide/credentials#using-aws-access-keys).

## Bref

Install Bref in your project using [Composer](https://getcomposer.org/):

```
composer require bref/bref
```

> To run the latest version of Bref you must have PHP 7.2 or greater! If you are using PHP 7.1 or less an older (outdated) version of Bref will be installed instead.

The `bref` command line tool can now be used by running `vendor/bin/bref` in your project.

## What's next?

Read the [first steps](/docs/first-steps.md) guide to create and deploy your first serverless application using Bref.

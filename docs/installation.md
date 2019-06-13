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

## AWS tooling

Bref relies on AWS SAM and AWS access keys to interact with AWS.

You will need to:

- [install AWS CLI](https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-install.html)
- [install AWS SAM CLI](https://aws.amazon.com/serverless/sam/)

- setup AWS credentials: [create AWS access keys](https://serverless.com/framework/docs/providers/aws/guide/credentials#creating-aws-access-keys) and [configure AWS CLI](https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-configure.html) by either:
    - running `aws configure` (quick configuration)
    - or by [using environment variables](https://docs.aws.amazon.com/cli/latest/userguide/cli-configure-envvars.html)

### Region

The default [region](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/using-regions-availability-zones.html) SAM will use is `us-east-1` (North Virginia, USA).

If you know you want to use a different region (for example to host your application closer to your visitors) you can define the `AWS_DEFAULT_REGION` environment variable. For example `export AWS_DEFAULT_REGION=eu-west-1` in your shell.

Alternatively the region can be overridden on every SAM command by setting the `--region` flag.

> If you are a first time user, using the `us-east-1` region (the default region) is *highly recommended* for the first projects. It simplifies commands and avoids a lot of mistakes when discovering AWS.

## Bref

Install Bref in your project using [Composer](https://getcomposer.org/):

```
composer require bref/bref
```

> To run the latest version of Bref you must have PHP 7.2 or greater! If you are using PHP 7.1 or less an older (outdated) version of Bref will be installed instead.

The `bref` command line tool can now be used by running `vendor/bin/bref` in your project.

## What's next?

Read the [first steps](/docs/runtimes/function.md) guide to create and deploy your first serverless application using Bref.

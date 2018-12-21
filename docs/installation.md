# Installation

Bref deploys PHP applications to [AWS Lambda](https://aws.amazon.com/lambda/).

To set up Bref correctly please complete all the sections below.

## AWS account

You will need an AWS account. To create one, go on [aws.amazon.com](https://aws.amazon.com/) and click *Sign up*.

## AWS tooling

Bref relies on AWS SAM and AWS access keys to interact with AWS.

You will need to:

- setup AWS credentials: [create AWS access keys](https://serverless.com/framework/docs/providers/aws/guide/credentials#creating-aws-access-keys) and either:
    - configure them [using environment variables](https://serverless.com/framework/docs/providers/aws/guide/credentials#quick-setup) (easy solution)
    - or [setup `aws-cli`](http://docs.aws.amazon.com/cli/latest/userguide/installing.html) and run `aws configure`
- [install AWS SAM CLI](https://github.com/awslabs/aws-sam-cli/blob/develop/docs/installation.rst)

## Bref

Install Bref in your project using [Composer](https://getcomposer.org/):

```
composer require mnapoli/bref
```

The `bref` command line tool can now be used by running `vendor/bin/bref` in your project.

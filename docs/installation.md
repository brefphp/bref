# Installation

To set up Bref correctly please complete all the sections below.

## AWS account

You will need an AWS account. To create one, go on [aws.amazon.com](https://aws.amazon.com/) and click *Sign up*.

AWS has a generous free tier that will usually allow you to deploy your first test applications for free.

## AWS tooling

Bref relies on AWS SAM and AWS access keys to interact with AWS.

You will need to:

- setup AWS credentials: [create AWS access keys](https://serverless.com/framework/docs/providers/aws/guide/credentials#creating-aws-access-keys) and either:
    - configure them [using environment variables](https://serverless.com/framework/docs/providers/aws/guide/credentials#quick-setup) (easy solution)
    - or [setup `aws-cli`](http://docs.aws.amazon.com/cli/latest/userguide/installing.html) and run `aws configure`
- [install AWS SAM CLI](https://github.com/awslabs/aws-sam-cli/blob/develop/docs/installation.rst)

### Region

The default [region](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/using-regions-availability-zones.html) SAM will use is `us-east-1` (North Virginia, USA).

If you know you want to use a different region (for example to host your application closer to your visitors) you can define the `AWS_DEFAULT_REGION` environment variable. For example `export AWS_DEFAULT_REGION=eu-west-1` in your shell.

Alternatively the region can be overridden on every SAM command by setting the `--region` flag.

## Bref

Install Bref in your project using [Composer](https://getcomposer.org/):

```
composer require mnapoli/bref
```

The `bref` command line tool can now be used by running `vendor/bin/bref` in your project.

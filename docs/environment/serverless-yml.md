---
title: serverless.yml
current_menu: serverless-yml
introduction: Configure your application with the serverless.yml file.
---

Your application is deployed using the Serverless framework based on the `serverless.yml` configuration file.

This page introduces a few advanced concepts of the `serverless.yml` format. You can learn in the [official Serverless documentation](https://serverless.com/framework/docs/providers/aws/).

## Overview

```yaml
service: app

provider:
    name: aws
    runtime: provided

plugins:
    - ./vendor/bref/bref

functions:
    foo:
        handler: index.php
        layers:
            - ${bref:layer.php-73} # PHP

resources:
    Resources:
        MyBucket:
            Type: AWS::S3::Bucket
            Properties:
                BucketName: 'my-bucket'
```

## Service

```yaml
service: app
```

The [service](https://serverless.com/framework/docs/providers/aws/guide/services/) is simply the name of your project.

Since Serverless lets us deploy a project in multiple stages (prod, dev, staging…), CloudFormation stacks will contain both the service name and the stage: `app-prod`, `app-dev`, etc.

## Provider

```yaml
provider:
    name: aws
```

Bref only supports the `aws` provider, even though Serverless can deploy applications on other cloud providers like Google Cloud, Azure, etc.

```yaml
provider:
    name: aws
    # The AWS region in which to deploy (us-east-1 by default)
    region: us-east-1
    # The stage of the application, e.g. dev, prod, staging… ('dev' by default)
    stage: dev
```

The `provider` section also lets us configure global options on all functions:

```yaml
provider:
    name: aws
    timeout: 10
    runtime: provided

functions:
    foo:
        handler: foo.php
        layers:
            - ${bref:layer.php-73}
    bar:
        handler: bar.php
        layers:
            - ${bref:layer.php-73}

# ...
```

is the same as:

```yaml
provider:
    name: aws

functions:
    foo:
        handler: foo.php
        timeout: 10
        runtime: provided
        layers:
            - ${bref:layer.php-73}
    bar:
        handler: bar.php
        timeout: 10
        runtime: provided
        layers:
            - ${bref:layer.php-73}

# ...
```

## Plugins

```yaml
plugins:
    - ./vendor/bref/bref
```

[Serverless plugins](https://serverless.com/framework/docs/providers/aws/guide/plugins/) are JavaScript plugins that extend the behavior of the Serverless framework.

Bref provides a plugin via the Composer package, which explains why the path is a relative path into the `vendor` directory. This plugin provides [variables to easily use Bref layers](http://localhost:8000/docs/runtimes/#usage), it is necessary to include it for the `${bref:layer.xxx}` variables to work.

Most other Serverless plugins [are installed via `npm`](https://serverless.com/framework/docs/providers/aws/guide/plugins/).

You can find the list of [all Serverless plugins here](https://serverless.com/plugins/).

## Functions

```yaml
functions:
    foo:
        handler: foo.php
        layers:
            - ${bref:layer.php-73}
    bar:
        handler: bar.php
        layers:
            - ${bref:layer.php-73}
```

Functions are AWS Lambda functions. You can find all options available [in this Serverless documentation page](https://serverless.com/framework/docs/providers/aws/guide/functions/).

Note that it is possible to mix PHP functions with functions written in other languages. The PHP support provided by Bref works via the AWS Lambda layers.

### Permissions

If your lambda needs to access other AWS services (S3, SQS, SNS…), you will need to add the proper permissions via the [`iamRoleStatements` section](https://serverless.com/framework/docs/providers/aws/guide/functions#permissions):

```yaml
provider:
    name: aws
    timeout: 10
    runtime: provided
    iamRoleStatements:
        # Allow to put a file in the `my-bucket` S3 bucket
        -   Effect: Allow
            Action: s3:PutObject
            Resource: 'arn:aws:s3:::my-bucket/*'
        # Allow to query and update the `example` DynamoDB table
        -   Effect: Allow
            Action:
                - dynamodb:Query
                - dynamodb:Scan
                - dynamodb:GetItem
                - dynamodb:PutItem
                - dynamodb:UpdateItem
                - dynamodb:DeleteItem
            Resource: 'arn:aws:dynamodb:us-east-1:111110002222:table/example'
```

If you only want to define some permissions **per function**, instead of globally (ie: in the provider), you should install and enable the Serverless plugin [`serverless-iam-roles-per-function`](https://github.com/functionalone/serverless-iam-roles-per-function) and then use the `iamRoleStatements` at the function definition block:

```yaml
functions:
    foo:
        handler: foo.php
        layers:
            - ${bref:layer.php-73}
        iamRoleStatements:
            # Allow to put a file in the `my-bucket` S3 bucket
            -   Effect: Allow
                Action: s3:PutObject
                Resource: 'arn:aws:s3:::my-bucket/*'
            # Allow to query and update the `example` DynamoDB table
            -   Effect: Allow
                Action:
                    - dynamodb:Query
                    - dynamodb:Scan
                    - dynamodb:GetItem
                    - dynamodb:PutItem
                    - dynamodb:UpdateItem
                    - dynamodb:DeleteItem
                Resource: 'arn:aws:dynamodb:us-east-1:111110002222:table/example'
```

## Resources

```yaml
resources:
    Resources:
        MyBucket:
            Type: AWS::S3::Bucket
            Properties:
                BucketName: 'my-bucket'
```

The `resources` section contains raw [CloudFormation syntax](https://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/template-reference.html). This lets us define any kind of AWS resource other than Lambda functions.

Read more in the [Serverless documentation about resources](https://serverless.com/framework/docs/providers/aws/guide/resources/).

Be careful, the CloudFormation resources must be defined in the `resources.Resources` sub-section:

```yaml
resources:
    Resources:
        # ...
```

### References

The CloudFormation `!Ref` syntax can be used. However the `${MyResource.Arn}` CloudFormation syntax cannot be used.

To solve this, the [serverless-pseudo-parameters plugin](https://github.com/svdgraaf/serverless-pseudo-parameters) can help. After installing it, you can reference other resources by replacing the `${...}` syntax with `#{...}` (because `${...}` is conflicting with [serverless.yml native variables](https://serverless.com/framework/docs/providers/aws/guide/variables/)).

Here is an example where we define a S3 bucket and a policy that references it. It uses both the `!Ref MyBucket` and `#{MyBucket.Arn}` syntaxes:

```yaml
#...

plugins:
    - serverless-pseudo-parameters

resources:
    Resources:
        MyBucket:
            Type: AWS::S3::Bucket
        # IAM policy that makes the bucket publicly readable
        MyBucketPolicy:
            Type: AWS::S3::BucketPolicy
            Properties:
                Bucket: !Ref MyBucket
                PolicyDocument:
                    Statement:
                        -   Effect: Allow
                            Principal: '*' # everyone
                            Action: s3:GetObject
                            Resource: '#{MyBucket.Arn}/*'
```

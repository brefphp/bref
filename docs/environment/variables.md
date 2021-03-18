---
title: Environment variables
current_menu: variables
introduction: Define environment variables for your Bref application.
---

Environment variables are the perfect solution to configure the application (as recommended in the [12 factor guide](https://12factor.net/config)).

## Definition

Environment variables can be defined in `serverless.yml`.

To define an environment variable that will be available in **all functions** declare it in the `provider` section:

```yaml
provider:
    # ...
    environment:
        MY_VARIABLE: 'my value'
```

To define an environment variable that will be available in **a specific function** declare it inside the function's properties:

```yaml
functions:
    foo:
        # ...
        environment:
            MY_VARIABLE: 'my value'
```

> Do not store secret values in `serverless.yml` directly. Check out the next section to handle secrets.

## Secrets

Secrets (API tokens, database passwords, etc.) should not be defined in `serverless.yml` and committed into your git repository.

Instead you can use the [SSM parameter store](https://docs.aws.amazon.com/systems-manager/latest/userguide/systems-manager-paramstore.html), a free service provided by AWS.

To create a parameter, you can do it via the [AWS SSM console](https://console.aws.amazon.com/systems-manager/parameters) or in the Bref Dashboard by running:

```bash
vendor/bin/bref dashboard
```

You can also do it in the CLI via the following command:

```bash
aws ssm put-parameter --region us-east-1 --name '/my-app/my-parameter' --type String --value 'mysecretvalue'
```

For Windows users, the first part of the path needs to be double slashes and all subsequent forward slashes changed to backslashes:
```bash
aws ssm put-parameter --region us-east-1 --name '//my-app\my-parameter' --type String --value 'mysecretvalue'
```

It is recommended to prefix the parameter name with your application name, for example: `/my-app/my-parameter`.

To import the SSM parameter into an environment variable you can use the [`${ssm:<parameter>}` syntax](https://serverless.com/blog/serverless-secrets-api-keys/):

```yaml
provider:
    # ...
    environment:
        MY_PARAMETER: ${ssm:/my-app/my-parameter}
```

### An alternative: AWS Secrets Manager

As an alternative you can also store secrets in [AWS Secrets Manager](https://aws.amazon.com/secrets-manager/). This solution, while very similar to SSM, will provide:

- better permission management using IAM
- JSON values, allowing to store multiple values in one parameter

However Secrets Manager is not free: [pricing details](https://aws.amazon.com/secrets-manager/pricing/).

## Local development

When [developing locally using `vendor/bin/bref local`](/docs/local-development.md), you can set environment variables using bash:

```bash
VAR1=val1 VAR2=val2 vendor/bin/bref local <funtion>

# Or using `export`:
export VAR1=val1
export VAR2=val2
vendor/bin/bref local <funtion>
```

## Learn more

While this page mentions environment variables, `serverless.yml` allows other types of variables to be used.

Read the [`serverless.yml` variables](https://serverless.com/framework/docs/providers/aws/guide/variables/) documentation to learn more.

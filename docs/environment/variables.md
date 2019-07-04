---
title: Environment variables
currentMenu: php
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

To create a parameter you can either do it manually in the [SSM parameter store console](https://console.aws.amazon.com/systems-manager/parameters) or use the following command:

```bash
aws ssm put-parameter --region us-east-1 --name '/my-app/my-parameter' --type String --value 'mysecretvalue'
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

When [developing locally using SAM](/docs/local-development.md) you can override environment variables via the `--env-vars` option:

```bash
sam local invoke <Function> --env-vars env.json
```

The `env.json` JSON file can either define environment variables for **all functions** using the `Parameters` key:

```json
{
    "Parameters": {
        "API_KEY": "8358deb1-ffb4-4077-90d7"
    }
}
```

or for individual functions using the name of the function in `template.yaml`:

```json
{
    "WebsiteFunction": {
        "API_KEY": "99016f5d-ab7e-4a80-9892"
    },
    "ConsoleFunction": {
        "API_KEY": "8358deb1-ffb4-4077-90d7"
    }
}
```

## Learn more

While this page mentions environment variables, `serverless.yml` allows other types of variables to be used.

Read the [`serverless.yml` variables](https://serverless.com/framework/docs/providers/aws/guide/variables/) documentation to learn more.

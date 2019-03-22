---
title: Environment variables
currentMenu: php
introduction: Define environment variables for your Bref application.
---

Environment variables are the perfect solution to configure the application (as recommended in the [12 factor guide](https://12factor.net/config)).

## Definition

Environment variables can be defined in `template.yml`.

To define an environment variable that will be available in **all functions** declare it in the `Globals` section:

```yaml
# Define your global variables in the `Globals` section
Globals:
    Function:
        Environment:
            Variables:
                MY_VARIABLE: 'my value'

# Define your functions in the `Resources` section
Resources:
    # ...
```

To define an environment variable that will be available in **a specific function** declare it inside the function's properties:

```yaml
Resources:
    MyFunction:
        Type: AWS::Serverless::Function
        Properties:
            # ...
            Environment:
                Variables:
                    MY_VARIABLE: 'my value'
```

> Do not store secret values in `template.yaml` directly. Check out the next section to handle secrets.

## Secrets

Secrets (API tokens, database passwords, etc.) should not be defined in `template.yaml` and committed into your git repository.

Instead you can use the [SSM parameter store](https://docs.aws.amazon.com/systems-manager/latest/userguide/systems-manager-paramstore.html), a free service provided by AWS.

To create a parameter:

- go into the [SSM parameter store console](https://console.aws.amazon.com/systems-manager/parameters) and make sure you are in the same region as your application
- click "Create parameter"
- it is recommended to prefix the parameter name with your application name, e.g. `/my-app/my-parameter`
- set the secret in "Value" and save

To import the SSM parameter into an environment variable you can use a [dynamic reference](https://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/dynamic-references.html): `{{resolve:ssm:<parameter>:<version>}}`, for example:

```yaml
        Environment:
            Variables:
                MY_PARAMETER: '{{resolve:ssm:/my-app/my-parameter:1}}'
```

> Remember to update the parameter version in `template.yaml` anytime you change the value of the parameter. You will need to redeploy the application as well.

### An alternative: AWS Secrets Manager

As an alternative you can also store secrets in [AWS Secrets Manager](https://aws.amazon.com/secrets-manager/). This solution, while very similar to SSM, will provide:

- better permission management using IAM
- JSON values, allowing to store multiple values in one parameter
- the version can be omitted from the [`{{resolve:secretsmanager:...}}` syntax](https://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/dynamic-references.html#dynamic-references-secretsmanager)

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

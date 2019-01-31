---
title: Environment variables
currentMenu: php
introduction: Define environment variables for your Bref application.
---

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

The secrets (e.g. database passwords) must however not be committed in this file: define them in the [AWS Console](https://console.aws.amazon.com) or configure your ci/cd pipeline accordingly:

```bash
# Configure (e.g. passing secret env vars - existing env vars defined in template.yaml will be replaced!)
aws lambda update-function-configuration
  --function-name <function-name>
  --environment '{"Variables":{
    "SECRET_ENV_VAR":"'"$SECRET_ENV_VAR_VALUE_FROM_CICD"'",
  }}' > /dev/null
```
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

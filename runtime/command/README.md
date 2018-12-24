The "command" layer is a layer that comes on top of the PHP runtime. It lets us execute CLI commands on lambda.

This layer overrides the `bootstrap` to execute CLI console commands (e.g. Symfony Console or Laravel Artisan).

Usage example:

```yaml
Resources:
    MyFunction:
        Type: AWS::Serverless::Function
        Properties:
            [...]
            Handler: bin/console
            Layers:
                - '<PHP layer ARN here>'
                - '<command layer ARN here>'
```

Then to execute a command:

```php
vendor/bin/bref cli -- doctrine:migrate
```

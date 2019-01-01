# Configuring PHP

## php.ini

Bref's default `php.ini` is `/opt/php.ini` in your lambda.

You can create extra configuration files to customize it:

1. create a subdirectory in your project (e.g. `php/`)
1. create a `php.ini` file inside that directory (the name of the file does not matter)
1. define the [`PHP_INI_SCAN_DIR` environment variable](http://php.net/manual/en/configuration.file.php#configuration.file.scan) to point to that new directory

> The `PHP_INI_SCAN_DIR` must contain an absolute path. Since your code is placed in `/var/task` on AWS Lambda the environment variable should contain something like `/var/task/php`.

Here is an example of how to define it in your SAM template:

```yaml
Resources:
    MyFunction:
        Type: AWS::Serverless::Function
        Properties:
            # ...
            Environment:
                Variables:
                    PHP_INI_SCAN_DIR: '/var/task/php'
```

## Extensions

Some extensions are bundled by default in the PHP layer. Bref intends to include the most common extensions by default. If a major PHP extension is missing please send a pull request to add it.

Any other extension can be added through extra AWS layers by putting them in the `/opt/php/extensions` directory and loading them via `php.ini`.

### Installed extensions

Here is the list of extensions installed but disabled by default:

- Intl: `intl`
- MongoDB: `mongodb`
- Redis: `redis`

You can enable these extensions by loading them in php.ini, for example:

```ini
extension=intl
extension=redis
```

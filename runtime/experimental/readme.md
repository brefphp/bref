## Experimental Runtime Build System
This system uses a chain of Docker Containers to build PHP, associated extensions, and any other executables. To achieve this, we use four seperate containers:

1. **compiler** - This container is the base of our system and it is here that we install and configure all the required build tools. Nothing in this container will show up in the Layer we build. [See [compiler.Dockerfile](compiler.Dockerfile)]
2. **php** -- This is the container, built on *compilers*, in which we compile PHP and any libraries or extensions. [See [php.Dockerfile](php.Dockerfile)]
4. **export** -- This is the container, built from *php*, in which we package our build and export it from the container to the host system. [See [export.Dockerfile](export.Dockerfile)]

We currently support building either **PHP 7.2**.

## Usage
From this directory, simply type:

*Generate PHP 7.2*
```bash
export S3_BUCKET=YOURS3BUCKET
export REGION=YOURREGION
make php
ls exports/
cd tests/default
sam local start-api --region us-east-1
```
### What does that actually do?
First, it either creates the 'bref/runtime/compiler:latest' image, or verifies that it is properly cached. Next, we build php by creating the 'bref/runtime/php:latest' image, or verifying that it is properly cached. Then, we build the 'bref/runtime/dist:latest', it is never cached. From it, we copy out the zip files to the local hosts `exports` directory.

 * **php-7.2.zip** - This contains everything we could possibly build.
 * **php-7.2-fpm.zip** - Copy of php-7.2.zip, removes the CLI SAPI, only provides the FPM SAPI.
 * **php-72-cli.zip** - Copy of php-7.2.zip, removes the FPM SAPI, only provides the CLI SAPI.

Once that is done, we publish the Layer based on the zip file _(currently only php-72-cli.zip)_ and set the permissions on it. Finally, we copy `tests/default/template.yaml.static` to `tests/default/template.yaml` and update the layer in it before we publish the test to Lambda.

When you move into the `tests/default` layer and run the test locally, `http://127.0.0.1:3000/` should return `{"test":"success"}`.

If you want to make your own function, just look at [test.php](tests/default/test.php). Your function can be in any file and have any function name. Simply ensure `Handler: test.handler` is properly set in your own projects `template.yaml`.


## Configuration
You may edit versions, etc. in the *versions.ini* file.

The Makefile has some sane defaults set, that you can override with environment variables.

```make
REGION ?= us-east-2
BREF_VERSION ?= 0.3.0
PHP_VERSION_SHORT ?= 7.2
S3_BUCKET ?= bref-php-${REGION}
PHP_VERSION_SHORT_UNDERSCORE = $(subst .,_,${PHP_VERSION_SHORT})
```

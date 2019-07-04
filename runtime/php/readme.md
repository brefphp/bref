## Experimental Runtime Build System
This system uses a chain of Docker Containers to build PHP, associated extensions, and any other executables. To achieve this, we use two seperate containers:

1. **compiler** - This container is the base of our system and it is here that we install and configure all the required build tools. Nothing in this container will show up in the Layer we build. [See [compiler.Dockerfile](compiler.Dockerfile)]
2. **php** -- This is the container, built on *compilers*, in which we compile PHP and any libraries or extensions. [See [php.Dockerfile](php.Dockerfile)]. In this container we use a script [See [export.sh](export.sh)] in which we package our build and export it from the container to the host system.

We currently support building **PHP 7.2** and **PHP 7.3**.

## Usage
From this directory, simply type:

```bash
make distribution
ls exports/
```

### What does that actually do?
First, it either creates the 'bref/runtime/compiler:latest' image, or verifies that it is properly cached. Next, we build php by creating the 'bref/runtime/php:latest' image, or verifying that it is properly cached. Then, we build the 'bref/runtime/dist:latest', it is never cached. From it, we copy out the zip files to the local hosts `exports` directory.

 * **php-7.2.zip** - This contains everything we could possibly build.
 * **php-7.2-fpm.zip** - Copy of php-7.2.zip, removes the CLI SAPI, only provides the FPM SAPI.
 * **php-72-cli.zip** - Copy of php-7.2.zip, removes the FPM SAPI, only provides the CLI SAPI.

Once that is done, we publish the Layer based on the zip file _(currently only php-72-cli.zip)_ and set the permissions on it.

## Configuration
You may edit versions, etc. in the *versions.ini* file.

The Makefile has some sane defaults set, that you can override with environment variables.

```make
REGION ?= us-east-2
BREF_VERSION ?= 0.3.0
PHP_VERSION_SHORT ?= 7.2
PHP_VERSION_SHORT_UNDERSCORE = $(subst .,_,${PHP_VERSION_SHORT})
```

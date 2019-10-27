This directory contains the scripts that create and publish the AWS Lambda runtimes for PHP.

Read the [runtimes documentation](/docs/runtimes/README.md) to learn more.

## How it works

The scripts are written mainly in `Makefile`.

Multiple Docker images are created:

- `bref/tmp/...` images are created locally and not published online
- `bref/...` images are published on Docker Hub

Workflow:

- 1: Create the `bref/tmp/step-1/build-environment` Docker image.
    This image contains everything needed to compile PHP. This image is created standalone because
    it is common for each PHP version. The next step involves a different Dockerfile per PHP version.

- 2: Create the `bref/build-php-XX` images.
    There is one image per PHP version. These images contain PHP compiled with all its extensions.
    It is published so that anyone can use it to compile their own extensions.
    It is not the final image because it contains too many things that need to be removed.

- 3: Create the `bref/tmp/cleaned-build-php-XX` images.
    There is one image per PHP version. These images contain PHP compiled with all its extensions,
    but with all extra files removed. These images do not contain the bootstrap files and PHP config files.

- 4: Create the `bref/php-XX`, `bref/php-XX-fpm`, `bref/php-XX-fpm-dev` images.
    There is one image per PHP version. These images contain exactly what will be in the layers in `/opt`.

- 5: Create the layers zip files in the `export/` directory.
    We zip the `/opt` directory of each Docker image.

- 6: Publish the layers on AWS.
    We upload and publish the layers in every AWS region using the zip files.

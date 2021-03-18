# Bref runtimes and Docker images

Multiple Docker images are created:

- `bref/tmp/...` images are created locally and not published online
- `bref/...` images are published on Docker Hub

Goal for the Docker images:

- `bref/tmp/step-1/build-environment`
  
    This image contains everything needed to compile PHP. This image is created standalone because
    it is common for each PHP version. The next step involves a different Dockerfile per PHP version.

- `bref/build-php-XX`
  
    There is one image per PHP version. These images contain PHP compiled with all its extensions.
    It is published so that anyone can use it to compile their own extensions.
    It is not the final image because it contains too many things that need to be removed.

- `bref/tmp/cleaned-build-php-XX`
  
    There is one image per PHP version. These images contain PHP compiled with all its extensions,
    but with all extra files removed. These images do not contain the bootstrap files and PHP config files.

- `bref/php-XX`, `bref/php-XX-fpm`
  
    There is one image per PHP version. These images contain exactly what will be in the layers in `/opt`.

    These images have 3 goals:
  
    - export `/opt` to create layers
    - run applications locally using Docker: this requires overloading the entrypoint if necessary
    - deploy applications on Lambda using Docker images: set the handler as `cmd`

- `bref/php-XX-fpm-dev`

    Used to run web applications locally.

Layers are created in the `export/` directory (we zip the `/opt` directory of each Docker image).
We upload and publish the layers in every AWS region using the zip files.

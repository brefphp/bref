### Bref Runtime

Welcome to the internals of Bref! Here are some quick tips:

- To make a new PHP Version, copy `php81` into `php82`, search/replace `php81` with `php82` and change PHP_VERSION on `Makefile`.
- Check out `runtime/common/publish/generate-docker-compose.php` to add more regions.
- `runtime/common/utils/lib-check` is a small utility-tool to check whether we're copying unnecessary `.so` files into the Layer.
- `ldd` is a linux utility that will show libraries used by a binary e.g. `ldd /opt/bin/php` or `ldd /opt/php-modules/curl.so`

#### How Lambda Layers work?

In a nutshell, a Lambda Layer is a `zip` file containing files that are extracted into `/opt` when a Lambda
is invoked. Anything we want to make available inside AWS Lambda is possible by preparing the right files
and packing them into a layer. For these files to work properly, they need to be compatible with the
environment which they'll be executed. AWS provides Docker images (e.g. `public.ecr.aws/lambda/provided:al2-x86_64`)
with an exact replica of how the environment will look like.

#### Bref Runtime Structure

This project is designed for easily discarding old PHP versions and easily creating new ones. Almost everything
for a specific PHP Version stays inside `phpxx` folder. They are also optimized for packing single layers
for testing. Any contributor that wants to make changes to the layers will feel more confident about their
contribution if they can test them. A layer can be placed onto your own AWS Account by executing the following
steps:

- Edit `phpxx/Makefile` and add your own `AWS_PROFILE` at the top of the file.
- Run `make test` if you want to make sure the layers check are up-to-date.
- Run `make function` to deploy a Layer on your own account.
- You may choose which region layers get published at the top of `phpxx/Makefile`
- Run `make fpm` if you want to deploy an FPM Layer on your own account
# TODO: Console Layer.

#### runtime/common/function & runtime/common/fpm

In order to make the Runtime self-sufficient, we need to include some basic functionality from Bref inside
the layer itself. We do this by packaging the `src` folder as a composer installation inside the `runtime/common`
folders. Two Docker Images are generated with Bref and it's minimal dependencies. This is what allows users
to create Lambda Functions using the AWS Console and run their function on-the-fly (no composer or serverless deploy necessary).

#### runtime/common/publish

In order to publish layers in parallel, we use a `docker-compose` file with one container per region. That means
we can upload 21 layers in bulk. The containers expect a `TYPE` environment variable to locate and upload the zip
file. The zip files are generated and stored in `/tmp/bref-zip/` folder on the root machine.

#### The php{xx} folder

This is the heart of Bref Runtime Layers. We configure php using the `config` folder to store `php.ini` files that
will be copied into `/opt/php-ini`. Note that we configure `/opt/bootstrap` to execute PHP with `PHP_INI_SCAN_DIR=/opt/php-ini:/var/task/php/conf.d/`.
We also have a `Makefile` to facilitate the development, testing and building of layers. The command `make test`
will build Docker Images with everything necessary for Bref Runtime and then run containers with `runtime/tests` files
that will validate:

1- The PHP Binary and it's verion
2- The Core Extensions installed by default
3- The Additional Extensions Bref provide by default
4- The Disabled Extensions Bref provide by default
5- Function Invocation using AWS Runtime Interface Emulator
6- FPM Invocation using AWS Runtime Interface Emulator

The Dockerfile attempts at a best-effort to follow a top-down execution process for easier reading. It starts from
an AWS-provided Docker Image and installs PHP. Some standard files (such as the php binary) can already be
isolated into the `/bref` folder.

The 2nd layer is the `extensions` where all extensions are installed and isolated into the `/bref` folder.
Reminder that `ldd` is a linux utility that helps discover which files need isolating.

The 3rd layer is the `isolation` layer where we'll start from the standard AWS-provided image all over again
(getting rid of any residual unnecessary file) and then copying `/bref` into `/opt`. PHP Configurations are
copied here as well.

The 4th layer is the `function` layer where everything is packet together and the `bootstrap` file is loaded.
The `bref-internal-src` images (see runtime/common/function & runtime/common/fpm) are used to load Bref
classes into the layer.

The 5th layer is `zip-function`, where we get a small and fast Linux (Alpine) just to install and zip the entire
`/opt` content. We use docker-compose volumes to map `/tmp/bref-zip` from host to the container so that we can
zip everything and get the zipped file out of the container.

The 6th layer goes back to `extensions` and start `fpm-extension`. Here we're back at step 2 so that we can install
`fpm`.

The 7th layer goes back to `isolation` and start `fpm`. It mimics steps 3th and 4th but for the FPM Layer.

Lastly, layer 8th zips FPM and pack everything ready for AWS Lambda.

#### The Root Directory

The Root Directory was designed for Bref use only. Anybody is welcome to use it, but the intention is to prepare
every PHP version (in parallel) and then upload to every AWS Region at once. Contributors trying to test things
out will usually not want to "pollute" their AWS account by publishing the same layer on EVERY region. Hence,
the `Makefile` on each PHP Version folder being more useful for contributors since it allows to publish
a single layer on a single AWS Region.

## Experimental Runtime Build System
This system uses a chain of Docker Containers to build PHP, associated extensions, and any other executables. To achieve this, we use four seperate containers:

1. *compiler* - This container is the base of our system and it is here that we install and configure all the required build tools. [See compiler.Dockerfile]
2. *libraries* - This is the container, built from *compiler*, in which we compile and install all the non-php libraries that we require in order to build PHP. [See libs.Dockerfile]
3. *php* -- This is the container, built from *libraries*, in which we compile PHP and any extensions. [See php.Dockerfile]
4. *export* -- This is the container, built from *php*, in which we package our build and export it from the container to the host system. [See export.Dockerfile]

We currently support building either PHP 7.2 or PHP 7.3.

## Usage
From this directory, simply type:

*Generate PHP 7.2*
```bash
make php72
ls exports/
```

*Generate PHP 7.3*
```bash
make php73
ls exports/
```
## Configuration
You may edit versions, etc. in the *versions.ini* file.

## Make Commands
### compiler
`make compiler` will generate the compiler container. This container is rarely modified and after the intial build, it will not be built again unless modified.

### shell/compiler
`make shell/compiler` is a helper command to shell into the container after it is created. This is useful for debugging/developing.

### libs
`make libs` generates the container responsible for compiling and installing our libraries. Once this container is built, it will only be rebuilt again if the libraries change.

### shell/libs
`make shell/libs` is a helper command to shell into the container after it is created. This is useful for debugging/developing.

### php73
`make php73` will verify or do everything necessary to generate the PHP 7.3 zip packages.

### make_php73
`make make_php73` compiles PHP 7.3

### shell/php73
`make shell/php73` is a helper command to shell into the container after it is created. This is useful for debugging/developing.

### php72
`make php72` will verify or do everything necessary to generate the PHP 7.3 zip packages.

### make_php72
`make make_php72` compiles PHP 7.3

### shell/php72
`make shell/php72` is a helper command to shell into the container after it is created. This is useful for debugging/developing.

### distro:
`make distro` generates the container that we create the packages in.

### shell/distro: 
`make shell/distro` is a helper command to shell into the container after it is created. This is useful for debugging/developing.

### export
`make export` generates the packages in the distro container, then moves them to the host container in `exports/` directory.

### clean
`make clean` Cleans up any old/unused docker layers.

### publish/php73:
**NOTE** - Not fully implemented/test. Intended to hand moving the packages to s3, registering the layers, etc.

### publish/php72:
**NOTE** - Not fully implemented/test. Intended to hand moving the packages to s3, registering the layers, etc.

## Dev Notes
The real work of building is done via the Makefiles in the `makefiles` directory. Each makefile follow a pattern of:

1. Download the code
2. Configure the code
3. Build the code
4. Install the code

Look into the `libs.Dockerfile` to see how we use Docker ARGs, to create the ENV vars, then copy over the makefile and run it. By breaking out each build like this, we ensure that we do not have to rebuild anything unnecessarily. For example, if we simply defined all the ENV vars at the top of the file, anytime the value of one of them changes, Docker would rebuild everything for the top of the file. However, now, if we change an ARG, we only have to rebuild from that ENV variable onward.

This works in our favor because many of the libraries might have their own dependencies. If we update the openssl version, all the other libraries _(like curl)_ that rely on it will get rebuilt against the new version. However, openssl itself relies on zlib, which would not be rebuilt.
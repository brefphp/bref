# The container we build here is merely for creating a sane build environment
# for AWS Lambda. Nothing installed or built in this container should ever
# be packaged into a Lambda Task nor Layer.
#
# Lambda tasks use the amzn-ami-hvm-2018.03.0.20181129-x86_64-gp2 AMI, as
# documented under the AWS Lambda Runtimes.
#
# https://docs.aws.amazon.com/lambda/latest/dg/current-supported-versions.html
# AWS has kindly provided us with it as a base docker image.
# https://github.com/aws/amazon-linux-docker-images/tree/2018.03
FROM amazonlinux:2018.03
LABEL authors="Bubba Hines <bubba@stechstudio.com>"
LABEL vendor1="Signature Tech Studio, Inc."
LABEL vendor2="bref"
LABEL home="https://github.com/brefphp/bref"


# Working Directory
WORKDIR /tmp


# Lambda is based on 2018.03. Lock YUM to that release version.
RUN sed -i 's/releasever=latest/releaserver=2018.03/' /etc/yum.conf


RUN set -xe \
# Download yum repository data to cache
 && yum makecache \
# Default Development Tools
 && yum groupinstall -y "Development Tools"  --setopt=group_package_types=mandatory,default \
# PHP will use gcc 7.2 (installed because of `kernel-devel`) to compile itself.
# But the intl extension is C++ code. Since gcc-c++ 7.2 is not installed by default, gcc-c++ 4 will be used.
# The mismatch breaks the build, see https://github.com/brefphp/bref/pull/373
# To fix this, we install gcc-c++ 7.2. We also install gcc 7.2 explicitly to make sure we keep the same
# version in the future.
 && yum install -y gcc72 gcc72-c++


# CMAKE - cross-platform family of tools designed to build, test and package software. The
# version of cmake we can get from the yum repo is 2.8.12. We need cmake to build a few of
# our libraries, and at least one library requires a version of cmake greater than the one
# provided in the repo.
#
# Needed to build:
# - libzip: minimum required CMAKE version 3.0.2
# - libssh2: minimum required CMAKE version 2.8.11
RUN  set -xe \
 && mkdir -p /tmp/cmake \
 && cd /tmp/cmake \
 && curl -Ls  https://github.com/Kitware/CMake/releases/download/v3.13.2/cmake-3.13.2.tar.gz \
  | tar xzC /tmp/cmake --strip-components=1 \
 && ./bootstrap --prefix=/usr/local \
 && make \
 && make install

# BISON parser - 3.0 - required by PHP
RUN  set -xe \
 && mkdir -p /tmp/bison \
 && cd /tmp/bison \
 && curl -Ls https://ftp.gnu.org/gnu/bison/bison-3.4.1.tar.gz \
  | tar xzC /tmp/bison --strip-components=1 \
 && ./configure --prefix=/usr/local \
 && make \
 && make install

# rec2c - required by PHP
RUN  set -xe \
 && mkdir -p /tmp/rec2c \
 && cd /tmp/rec2c \
 && curl -Ls https://github.com/skvadrik/re2c/releases/download/0.13.6/re2c-0.13.6.tar.gz \
  | tar xzC /tmp/rec2c --strip-components=1 \
 && ./configure --prefix=/usr/local \
 && make \
 && make install

# oniguruma - required by PHP
RUN  set -xe \
 && mkdir -p /tmp/oniguruma \
 && cd /tmp/oniguruma \
 && curl -Ls https://github.com/kkos/oniguruma/releases/download/v6.9.3/onig-6.9.3.tar.gz \
  | tar xzC /tmp/oniguruma --strip-components=1 \
 && ./configure --prefix=/opt/bref/lib/pkgconfig \
 && make \
 && make install

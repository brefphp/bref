FROM amazonlinux:2017.03 as compiler
LABEL authors="Bubba Hines <bubba@stechstudio.com>"
LABEL vendor1="Signature Tech Studio, Inc."
LABEL vendor2="bref"
LABEL home="https://github.com/mnapoli/bref"

ENV DEPS="/deps" \
    TARGET="/opt/bref"

RUN mkdir -p ${DEPS}  \
    ${TARGET}/bin \
    ${TARGET}/doc \
    ${TARGET}/etc \
    ${TARGET}/include \
    ${TARGET}/lib \
    ${TARGET}/lib64 \
    ${TARGET}/libexec \
    ${TARGET}/sbin \
    ${TARGET}/share

# Working Directory
WORKDIR ${TARGET}

# Lambda is based on 2017.03. Lock YUM to that release version.
RUN sed -i 's/releasever=latest/releaserver=2017.03/' /etc/yum.conf
RUN yum makecache

# Default Development Tools
RUN yum groupinstall -y "Development Tools"  --setopt=group_package_types=mandatory,default

# Additional Tools
# jq - Used for manipulating json on the command line
# gperf - Perfect has generator, some of the build will use it.
# expect - Allows us to automate some build scripts that require interaction.
# gtk-doc - Generates API Docs for C code.
# texlive - Used by some builds for generating documentation.
# docbook2X - Converts docbook documents into Unix man page format.
# python35 - Because 2.7 needs to go away? Some of our builds want python3 libs.
# python35-pip - See above.
# findutils - Basic directory searching utilities of the GNU operating system.
# yum clean all - Ensure we are not storing MB's of downloaded RPM files in our Docker images
RUN yum install -y  jq           \
                    gperf        \
                    expect       \
                    gtk-doc      \
                    texlive      \
                    docbook2X    \
                    findutils    \
                    && yum clean all

# CMAKE - cross-platform family of tools designed to build, test and package software. The version of cmake we can get from the yum repo is 2.8.12. We need cmake to build a few of our libraries, and at least one library requires a version of cmake greater than the one provided in the repo.

# Needed to build:
# - libzip: minimum required CMAKE version 3.0.2
# - libssh2: minimum required CMAKE version 2.8.11
# - jpeg-turbo: minimum required CMAKE version 2.8.12
RUN mkdir -p /tmp/cmake && \
    cd /tmp/cmake && \
    curl -Ls  https://github.com/Kitware/CMake/releases/download/v3.13.2/cmake-3.13.2.tar.gz | tar xzC /tmp/cmake --strip-components=1 && \
    ./bootstrap --prefix=/usr/local && \
    make && \
    make install

# Copy over our helper script
COPY helpers/get_tar_args.sh /usr/local/bin/get_tar_args.sh

# Set some sane environment variables for ourselves
ENV \
    VERSIONS_FILE="${TARGET}/versions.json" \
    BUILD_LOGS="/building/logs" \
    PKG_CONFIG_PATH="${TARGET}/lib64/pkgconfig:${TARGET}/lib/pkgconfig" \
    PKG_CONFIG="/usr/bin/pkg-config" \
    PATH="${TARGET}/bin:${PATH}" \
    CPPFLAGS="-I${TARGET}/include  -I/usr/include" \
    LDFLAGS="-L${TARGET}/lib64 -L${TARGET}/lib -Wl,--gc-sections" \
    FLAGS="--strip-all -Os " \
    CFLAGS="${FLAGS}" \
    CXXFLAGS="${FLAGS} -ffunction-sections -fdata-sections" \
    LD_LIBRARY_PATH="${TARGET}/lib64:${TARGET}/lib" \
    SOURCEFORGE_MIRROR="netix" \
    PATH="${TARGET}/sbin:${TARGET}/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin" \
    JQ="/usr/bin/jq" \
    TARGS="/usr/local/bin/get_tar_args.sh" \
    CURL='${TARGET}/bin/curl' \
    CMAKE='/usr/local/bin/cmake'

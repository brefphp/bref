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

# Tools we need
RUN yum groupinstall -y "Development Tools"  --setopt=group_package_types=mandatory,default
RUN yum install -y  jq \
                    gperf \
                    expect \
                    gtk-doc \
                    texlive \
                    python35 \
                    docbook2X \
                    findutils \
                    python35-pip

RUN yum clean all

# Install Ninja and Meson
RUN curl -Ls https://github.com/ninja-build/ninja/releases/download/v1.8.2/ninja-linux.zip >> /tmp/ninja.zip && \
    cd /tmp && unzip /tmp/ninja.zip && \
    cp /tmp/ninja /usr/local/bin && \
    /usr/bin/pip-3.5 install meson

# Install the rust toolchain
RUN curl https://sh.rustup.rs -sSf | sh -s -- -y

# We need a newer cmake than is available, so lets build it ourselves.
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
    PATH="${TARGET}/sbin:${TARGET}/bin:/root/.cargo/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin" \
    JQ="/usr/bin/jq" \
    TARGS="/usr/local/bin/get_tar_args.sh" \
    CURL='${TARGET}/bin/curl' \
    CMAKE='/usr/local/bin/cmake' \
    MESON='/usr/local/bin/meson' \
    NINJA='/usr/local/bin/ninja'
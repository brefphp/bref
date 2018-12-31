FROM bref/runtime/compiler:latest
LABEL authors="Bubba Hines <bubba@stechstudio.com>"
LABEL vendor1="Signature Tech Studio, Inc."
LABEL vendor2="bref"
LABEL home="https://github.com/mnapoli/bref"

COPY helpers/versions.py /usr/local/bin/versions.py
RUN /usr/local/bin/versions.py init
 
# We are going to want these include files later.
RUN LD_LIBRARY_PATH= yum install -y gmp-devel readline-devel libicu-devel

# https://github.com/madler/zlib/releases
# Needed for:
#   - openssl
#   - curl
#   - pcre
ARG zlib
ENV VERSION_ZLIB=${zlib}
COPY makefiles/zlib.mk ${DEPS}/zlib.mk
RUN /usr/bin/make -f ${DEPS}/zlib.mk

# https://github.com/openssl/openssl/releases
# Needs:
#   - zlib
# Needed by:
#   - curl
#   - php
ARG openssl
ENV VERSION_OPENSSL=${openssl}
ENV CA_BUNDLE_SOURCE="https://curl.haxx.se/ca/cacert.pem"
ENV CA_BUNDLE="${TARGET}/ssl/cert.pem"
COPY makefiles/openssl.mk  ${DEPS}/openssl.mk 
RUN /usr/bin/make -f ${DEPS}/openssl.mk 

# https://github.com/libssh2/libssh2/releases
# Needs:
#   - None
# Needed by:
#   - curl
ARG libssh2
ENV VERSION_LIBSSH2=${libssh2}
COPY makefiles/libssh2.mk ${DEPS}/libssh2.mk
RUN /usr/bin/make -f ${DEPS}/libssh2.mk

# https://github.com/curl/curl/releases
# Needs:
#   - OpenSSL
#   - zlib
#   - libssh2
# Needed by:
#   - poppler
#   - php
ARG curl
ENV VERSION_CURL=${curl}
COPY makefiles/curl.mk ${DEPS}/curl.mk
RUN /usr/bin/make -f ${DEPS}/curl.mk

# https://github.com/nih-at/libzip
# Needs:
#   - None
# Needed by:
#   - php
ARG libzip
ENV VERSION_LIBZIP=${libzip}
COPY makefiles/libzip.mk ${DEPS}/libzip.mk
RUN /usr/bin/make -f ${DEPS}/libzip.mk

# http://www.linuxfromscratch.org/blfs/view/svn/general/nasm.html
# Needs:
#   - None
# Needed by:
#   - libjpeg-turbo
ARG nasm
ENV VERSION_NASM=${nasm}
COPY makefiles/nasm.mk ${DEPS}/nasm.mk
RUN /usr/bin/make -f ${DEPS}/nasm.mk

# https://github.com/GNOME/libxml2/releases
# http://www.linuxfromscratch.org/blfs/view/svn/general/libxml2.html
# Needs:
#   - zlib
# Needed by:
#   - php
arg libxml2
ENV VERSION_XML2=${libxml2}
COPY makefiles/xml2.mk ${DEPS}/xml2.mk
RUN /usr/bin/make -f ${DEPS}/xml2.mk

# https://github.com/libjpeg-turbo/libjpeg-turbo/releases
# http://www.linuxfromscratch.org/blfs/view/svn/general/libjpeg.html
# Needs:
#   - nasm
# Needed by:
#   - webp
ARG libjpeg
ENV VERSION_JPGTURBO=${libjpeg}
COPY makefiles/jpeg-turbo.mk ${DEPS}/jpeg-turbo.mk
RUN /usr/bin/make -f ${DEPS}/jpeg-turbo.mk

# https://libpng.sourceforge.io/
# http://www.linuxfromscratch.org/blfs/view/svn/general/libpng.html
# Needs:
#   - None
# Needed by:
#   - php
#   - webp
ARG libpng
ENV VERSION_PNG16=${libpng}
COPY makefiles/png16.mk  ${DEPS}/png16.mk 
RUN /usr/bin/make -f ${DEPS}/png16.mk 

# https://storage.googleapis.com/downloads.webmproject.org/releases/webp/index.html
# http://www.linuxfromscratch.org/blfs/view/8.0/general/libwebp.html
# Needs:
#   - libjpeg-turbo
#   - png16
# Needed by:
#   - php
ARG libwebp
ENV VERSION_WEBP=${libwebp}
COPY makefiles/webp.mk ${DEPS}/webp.mk
RUN /usr/bin/make -f ${DEPS}/webp.mk

# 
# https://github.com/postgres/postgres/releases
# Needs:
#   - 
# Needed by:
#   - php
ARG postgres
ENV VERSION_POSTGRES=${postgres}
COPY makefiles/postgres.mk ${DEPS}/postgres.mk
RUN /usr/bin/make -f ${DEPS}/postgres.mk

# https://github.com/jedisct1/libsodium/releases
# Needs:
#   - 
# Needed by:
#   - php
ARG sodium
ENV VERSION_SODIUM=${sodium}
COPY makefiles/sodium.mk ${DEPS}/sodium.mk
RUN /usr/bin/make -f ${DEPS}/sodium.mk

FROM sts/lambda/compiler:latest
WORKDIR ${TARGET}
RUN LD_LIBRARY_PATH= yum install -y gmp-devel readline-devel libicu-devel
ENV CA_BUNDLE_SOURCE="https://curl.haxx.se/ca/cacert.pem"
ENV CA_BUNDLE="${TARGET}/ssl/cert.pem"
COPY --from=0 /versions.json /versions.json
COPY --from=0 ${TARGET} ${TARGET}
COPY helpers/versions.py /usr/local/bin/versions.py
export AWS_TAG ?= al2-x86_64
export ARCHITECTURE ?= x86
export PHP_VERSION ?= php74
export LAYER = ${ARCHITECTURE}-${PHP_VERSION}-${TYPE}
export IMAGE = bref/${LAYER}
export REGION ?= eu-west-1

slim:
	# Build the base image individually so that the extensions can be built using it
	docker-compose -f docker-compose.base.${PHP_VERSION}.yml build ${PHP_VERSION}-base

	# Build all extensions upfront
	docker-compose -f docker-compose.base.${PHP_VERSION}.yml build --parallel

	# Build the Layer image and test it
	docker-compose -f docker-compose.slim.yml build ${PHP_VERSION}-slim
	docker-compose -f docker-compose.slim.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-slim /tests/unit/test_slim.php
	docker-compose -f docker-compose.slim.yml up -d ${PHP_VERSION}-slim
	docker-compose -f docker-compose.slim.yml run ${PHP_VERSION}-slim-tester
	docker-compose -f docker-compose.slim.yml stop

	rm /tmp/bref-zip/slim -rf && mkdir -p /tmp/bref-zip/slim
	docker-compose -f docker-compose.slim.yml run ${PHP_VERSION}-slim-zip
	TYPE=slim docker-compose -f docker-compose.upload.yml run ${REGION}

function:
	# Build the base image individually so that the extensions can be built using it
	docker-compose -f docker-compose.base.${PHP_VERSION}.yml build ${PHP_VERSION}-base

	# Build all extensions upfront
	docker-compose -f docker-compose.base.${PHP_VERSION}.yml build ${PHP_VERSION}-ext-default

	# Build the Layer image and test it
	docker-compose -f docker-compose.function.yml build ${PHP_VERSION}-function
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-function /tests/unit/test_slim.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-function /tests/unit/test_extensions.php
	docker-compose -f docker-compose.function.yml up -d ${PHP_VERSION}-function
	docker-compose -f docker-compose.function.yml run ${PHP_VERSION}-function-tester
	docker-compose -f docker-compose.function.yml stop

	rm /tmp/bref-zip/function -rf && mkdir -p /tmp/bref-zip/function
	docker-compose -f docker-compose.function.yml run ${PHP_VERSION}-function-zip
	TYPE=function docker-compose -f docker-compose.upload.yml run ${REGION}

fpm:
	# Build the base image individually so that the extensions can be built using it
	docker-compose -f docker-compose.base.${PHP_VERSION}.yml build ${PHP_VERSION}-base

	# Build all extensions upfront
	docker-compose -f docker-compose.base.${PHP_VERSION}.yml build ${PHP_VERSION}-ext-default

	docker-compose -f docker-compose.fpm.yml build bref-fpm ${PHP_VERSION}-ext-fpm ${PHP_VERSION}-fpm ${PHP_VERSION}-fpm-tester
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-fpm /tests/unit/test_slim.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-fpm /tests/unit/test_fpm.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-fpm /tests/unit/test_extensions.php
	docker-compose -f docker-compose.fpm.yml run ${PHP_VERSION}-fpm-config-tester
	docker-compose -f docker-compose.fpm.yml up -d ${PHP_VERSION}-fpm
	docker-compose -f docker-compose.fpm.yml run ${PHP_VERSION}-fpm-tester
	docker-compose -f docker-compose.fpm.yml stop

	rm /tmp/bref-zip/fpm -rf && mkdir -p /tmp/bref-zip/fpm
	docker-compose -f docker-compose.fpm.yml run ${PHP_VERSION}-fpm-zip
	TYPE=fpm docker-compose -f docker-compose.upload.yml run ${REGION}

everything:
	unset PHP_VERSION

	# Build the base image individually so that the extensions can be built using it
	docker-compose -f docker-compose.base.php74.yml \
                -f docker-compose.base.php80.yml \
                -f docker-compose.base.php81.yml \
                build --parallel php74-base php80-base php81-base

	# Build all extensions upfront
	docker-compose -f docker-compose.base.php74.yml \
                -f docker-compose.base.php80.yml \
                -f docker-compose.base.php81.yml \
                build --parallel

	# Build fpm extensions and Bref Package
	docker-compose -f docker-compose.fpm.yml build --parallel \
                bref-fpm \
                php74-ext-fpm php80-ext-fpm php81-ext-fpm

	# Build the Function and Fpm layers in parallel
	docker-compose -f docker-compose.function.yml \
				-f docker-compose.fpm.yml \
                build --parallel \
                php74-function php80-function php81-function \
                php74-fpm php80-fpm php81-fpm

	# Test Function Layer
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php74-function /tests/unit/test_slim.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php80-function /tests/unit/test_slim.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php81-function /tests/unit/test_slim.php

	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php74-function /tests/unit/test_extensions.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php80-function /tests/unit/test_extensions.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php81-function /tests/unit/test_extensions.php

	docker-compose -f docker-compose.function.yml up -d php74-function php80-function php81-function

	docker-compose -f docker-compose.function.yml up php74-function-tester php80-function-tester php81-function-tester

	docker-compose -f docker-compose.function.yml stop -t 0

	# Test FPM Layer
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php74-fpm /tests/unit/test_slim.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php80-fpm /tests/unit/test_slim.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php81-fpm /tests/unit/test_slim.php

	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php74-fpm /tests/unit/test_extensions.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php80-fpm /tests/unit/test_extensions.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php81-fpm /tests/unit/test_extensions.php

	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php74-fpm /tests/unit/test_fpm.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php80-fpm /tests/unit/test_fpm.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php81-fpm /tests/unit/test_fpm.php

	docker-compose -f docker-compose.fpm.yml up -d php74-fpm php80-fpm php81-fpm

	docker-compose -f docker-compose.fpm.yml up php74-fpm-tester php80-fpm-tester php81-fpm-tester

	docker-compose -f docker-compose.fpm.yml stop -t 0

	rm /tmp/bref-zip/slim -rf && mkdir -p /tmp/bref-zip/slim
	rm /tmp/bref-zip/function -rf && mkdir -p /tmp/bref-zip/function
	rm /tmp/bref-zip/fpm -rf && mkdir -p /tmp/bref-zip/fpm

	ARCHITECTURE=x86 docker-compose -f docker-compose.function.yml \
                -f docker-compose.fpm.yml \
                build \
                    php74-function-zip php80-function-zip php81-function-zip \
                    php74-fpm-zip php80-fpm-zip php81-fpm-zip

	docker-compose -f docker-compose.function.yml \
                -f docker-compose.fpm.yml \
                up \
                    php74-function-zip php80-function-zip php81-function-zip \
                    php74-fpm-zip php80-fpm-zip php81-fpm-zip

	TYPE=function docker-compose -f docker-compose.upload.yml up
	TYPE=fpm docker-compose -f docker-compose.upload.yml up
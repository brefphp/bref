export AWS_TAG ?= al2-x86_64
export ARCHITECTURE ?= x86
export PHP_VERSION ?= php74
export LAYER = ${ARCHITECTURE}-${PHP_VERSION}-${TYPE}
export IMAGE = bref/${LAYER}

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

	#rm /tmp/bref-zip/slim -rf && mkdir -p /tmp/bref-zip/slim
	#docker-compose -f docker-compose.publish.yml run zip
	#REGION=eu-west-1 docker-compose -f docker-compose.publish.yml run publish

function:
	# Build the base image individually so that the extensions can be built using it
	docker-compose -f docker-compose.base.${PHP_VERSION}.yml build ${PHP_VERSION}-base

	# Build all extensions upfront
	docker-compose -f docker-compose.base.${PHP_VERSION}.yml build --parallel

	# Build the Layer image and test it
	docker-compose -f docker-compose.function.yml build ${PHP_VERSION}-function
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-function /tests/unit/test_slim.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-function /tests/unit/test_extensions.php
	docker-compose -f docker-compose.function.yml up -d ${PHP_VERSION}-function
	docker-compose -f docker-compose.function.yml run ${PHP_VERSION}-function-tester
	docker-compose -f docker-compose.function.yml stop

	#rm /tmp/bref-zip/function -rf && mkdir -p /tmp/bref-zip/function
	#docker-compose -f docker-compose.publish.yml run zip
	#REGION=eu-west-1 docker-compose -f docker-compose.publish.yml run publish

fpm:
	# Build the base image individually so that the extensions can be built using it
	docker-compose -f docker-compose.base.${PHP_VERSION}.yml build ${PHP_VERSION}-base

	# Build all extensions upfront
	docker-compose -f docker-compose.base.${PHP_VERSION}.yml build --parallel

	docker-compose -f docker-compose.fpm.yml build bref-fpm ${PHP_VERSION}-fpm ${PHP_VERSION}-fpm-tester
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-fpm /tests/unit/test_slim.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-fpm /tests/unit/test_fpm.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php ${PHP_VERSION}-fpm /tests/unit/test_extensions.php
	docker-compose -f docker-compose.fpm.yml run ${PHP_VERSION}-fpm-config-tester
	docker-compose -f docker-compose.fpm.yml up -d ${PHP_VERSION}-fpm
	docker-compose -f docker-compose.fpm.yml run ${PHP_VERSION}-fpm-tester
	docker-compose -f docker-compose.fpm.yml stop

	#rm /tmp/bref-zip/fpm -rf && mkdir -p /tmp/bref-zip/fpm
	#docker-compose -f docker-compose.publish.yml run zip
	#REGION=eu-west-1 docker-compose -f docker-compose.publish.yml run publish

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

	# Build the slim layer image
	docker-compose -f docker-compose.slim.yml build --parallel php74-slim php80-slim php81-slim

	docker-compose -f docker-compose.slim.yml run --entrypoint /opt/bin/php php74-slim /tests/unit/test_slim.php
	docker-compose -f docker-compose.slim.yml run --entrypoint /opt/bin/php php80-slim /tests/unit/test_slim.php
	docker-compose -f docker-compose.slim.yml run --entrypoint /opt/bin/php php81-slim /tests/unit/test_slim.php

	docker-compose -f docker-compose.slim.yml up -d php74-slim php80-slim php81-slim

	docker-compose -f docker-compose.slim.yml up php74-slim-tester php80-slim-tester php81-slim-tester

	docker-compose -f docker-compose.slim.yml stop -t 0

	# Build the function layer image
	docker-compose -f docker-compose.function.yml build --parallel php74-function php80-function php81-function

	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php74-function /tests/unit/test_slim.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php80-function /tests/unit/test_slim.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php81-function /tests/unit/test_slim.php

	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php74-function /tests/unit/test_extensions.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php80-function /tests/unit/test_extensions.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php81-function /tests/unit/test_extensions.php

	docker-compose -f docker-compose.function.yml up -d php74-function php80-function php81-function

	docker-compose -f docker-compose.function.yml up php74-function-tester php80-function-tester php81-function-tester

	docker-compose -f docker-compose.function.yml stop -t 0

	# Build the fpm layer image
	docker-compose -f docker-compose.fpm.yml build bref-fpm
	docker-compose -f docker-compose.fpm.yml build --parallel php74-fpm php80-fpm php81-fpm

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

	#rm /tmp/bref-zip/slim -rf && mkdir -p /tmp/bref-zip/slim
	#docker-compose -f docker-compose.publish.yml run zip
	#REGION=eu-west-1 docker-compose -f docker-compose.publish.yml run publish
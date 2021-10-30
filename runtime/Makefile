export ARCHITECTURE ?= x86
export PHP_VERSION ?= php81
export LAYER = ${ARCHITECTURE}-${PHP_VERSION}-${TYPE}
export IMAGE = bref/${LAYER}

slim:
	# Build the base image individually so that the extensions can be built using it
	docker-compose -f docker-compose.compile.yml build php-base

	# Build all extensions upfront
	docker-compose -f docker-compose.compile.yml build --parallel

	# Build the Layer image and test it
	docker-compose -f docker-compose.function.slim.yml build php-slim
	docker-compose -f docker-compose.function.slim.yml run --entrypoint /opt/bin/php php-slim /tests/unit/test_slim.php
	docker-compose -f docker-compose.function.slim.yml up -d php-slim
	docker-compose -f docker-compose.function.slim.yml run php-slim-tester
	docker-compose -f docker-compose.function.slim.yml stop

	rm /tmp/bref-zip/slim -rf && mkdir -p /tmp/bref-zip/slim
	docker-compose -f docker-compose.publish.yml run zip
	#REGION=eu-west-1 docker-compose -f docker-compose.publish.yml run publish

function:
	# Build the base image individually so that the extensions can be built using it
	docker-compose -f docker-compose.compile.yml build php-base

	# Build all extensions upfront
	docker-compose -f docker-compose.compile.yml build --parallel

	# Build the Layer image and test it
	docker-compose -f docker-compose.function.yml build php-function
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php-function /tests/unit/test_slim.php
	docker-compose -f docker-compose.function.yml run --entrypoint /opt/bin/php php-function /tests/unit/test_extensions.php
	docker-compose -f docker-compose.function.yml up -d php-function
	docker-compose -f docker-compose.function.yml run php-function-tester
	docker-compose -f docker-compose.function.yml stop

	rm /tmp/bref-zip/function -rf && mkdir -p /tmp/bref-zip/function
	docker-compose -f docker-compose.publish.yml run zip
	#REGION=eu-west-1 docker-compose -f docker-compose.publish.yml run publish

fpm:
	# Build the base image individually so that the extensions can be built using it
	docker-compose -f docker-compose.compile.yml build php-base

	# Build all extensions upfront
	docker-compose -f docker-compose.compile.yml build --parallel

	docker-compose -f docker-compose.fpm.yml build bref-fpm php-fpm php-fpm-tester
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php-fpm /tests/unit/test_slim.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php-fpm /tests/unit/test_fpm.php
	docker-compose -f docker-compose.fpm.yml run --entrypoint /opt/bin/php php-fpm /tests/unit/test_extensions.php
	docker-compose -f docker-compose.fpm.yml run php-fpm-config-tester
	docker-compose -f docker-compose.fpm.yml up -d php-fpm php-fpm-tester
	docker-compose -f docker-compose.fpm.yml exec php-fpm-tester php /tests/integration/test_invoke_fpm.php
	docker-compose -f docker-compose.fpm.yml stop

	rm /tmp/bref-zip/fpm -rf && mkdir -p /tmp/bref-zip/fpm
	docker-compose -f docker-compose.publish.yml run zip
	#REGION=eu-west-1 docker-compose -f docker-compose.publish.yml run publish

layers:
	TYPE=slim $(MAKE) slim
	TYPE=function $(MAKE) function
	TYPE=fpm $(MAKE) fpm

everything:
	PHP_VERSION=php74 $(MAKE) layers
	PHP_VERSION=php80 $(MAKE) layers
	PHP_VERSION=php81 $(MAKE) layers
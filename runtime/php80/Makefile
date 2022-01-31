export CPU ?= x86
export REGION ?= eu-west-1
export AWS_PROFILE ?= deleugpn_brefphp
export ROOT_DIR = ../../
export PHP_VERSION = 8.0.16

.SILENT: function fpm

function:
	docker build -f ${ROOT_DIR}runtime/common/function/Dockerfile ${ROOT_DIR} -t bref/function-internal-src
	docker-compose build isolation function zip-function
	docker-compose run --rm --entrypoint /opt/bin/php isolation /opt/tests/test_1_binary.php ${PHP_VERSION}
	docker-compose run --rm --entrypoint /opt/bin/php isolation /opt/tests/test_2_default_extensions.php
	docker-compose run --rm --entrypoint /opt/bin/php isolation /opt/tests/test_3_additional_extensions.php
	docker-compose up -d function && docker-compose exec -T function /opt/bin/php /opt/tests/test_4_function_invocation.php
	docker-compose down
	rm /tmp/bref-zip/ -rf
	docker-compose run --rm zip-function
	LAYER_NAME=${CPU}-php80-function docker-compose -f ../common/publish/docker-compose.yml run publish

fpm:
	docker build -f ${ROOT_DIR}runtime/common/fpm/Dockerfile ${ROOT_DIR} -t bref/fpm-internal-src
	docker-compose build isolation fpm zip-fpm
	docker-compose run --rm --entrypoint /opt/bin/php fpm /opt/tests/test_1_binary.php ${PHP_VERSION}
	docker-compose run --rm --entrypoint /opt/bin/php fpm /opt/tests/test_2_default_extensions.php
	docker-compose run --rm --entrypoint /opt/bin/php fpm /opt/tests/test_3_additional_extensions.php
	docker-compose up -d fpm && docker-compose exec -T fpm /opt/bin/php /opt/tests/test_5_fpm_invocation.php
	docker-compose down
	rm /tmp/bref-zip/ -rf
	docker-compose build zip-fpm && docker-compose run --rm zip-fpm
	LAYER_NAME=${CPU}-php80-fpm docker-compose -f ../common/publish/docker-compose.yml run publish

test:
	$(MAKE) test_php_binary
	$(MAKE) test_default_extensions
	$(MAKE) test_additional_extensions
	$(MAKE) test_bref_file_isolation
	$(MAKE) test_function_invocation
	$(MAKE) test_fpm_invocation

test_php_binary:
	docker-compose build binary
	docker-compose run --rm --entrypoint /bref/bin/php binary /bref/tests/test_1_binary.php ${PHP_VERSION}

test_default_extensions:
	docker-compose build binary
	docker-compose run --rm --entrypoint /bref/bin/php binary /bref/tests/test_2_default_extensions.php

test_additional_extensions:
	docker-compose build extensions
	docker-compose run --rm --entrypoint /bref/bin/php extensions /bref/tests/test_3_additional_extensions.php

test_bref_file_isolation:
	docker-compose build isolation
	docker-compose run --rm --entrypoint /opt/bin/php isolation /opt/tests/test_1_binary.php ${PHP_VERSION}
	docker-compose run --rm --entrypoint /opt/bin/php isolation /opt/tests/test_2_default_extensions.php
	docker-compose run --rm --entrypoint /opt/bin/php isolation /opt/tests/test_3_additional_extensions.php
	docker-compose run --rm --entrypoint /opt/bin/php isolation /opt/tests/test_6_disabled_extensions.php
	docker-compose run --rm --entrypoint /opt/bin/php -e PHP_INI_SCAN_DIR="/opt/php-ini/:/opt/tests/" isolation /opt/tests/test_6_manual_enabling_extensions.php

test_function_invocation:
	docker build -f ${ROOT_DIR}runtime/common/function/Dockerfile ${ROOT_DIR} -t bref/function-internal-src
	docker-compose build function
	docker-compose up -d function
	docker-compose exec -T function /opt/bin/php /opt/tests/test_4_function_invocation.php
	docker-compose down

test_fpm_invocation:
	docker build -f ${ROOT_DIR}runtime/common/fpm/Dockerfile ${ROOT_DIR} -t bref/fpm-internal-src
	docker-compose build fpm
	docker-compose up -d fpm
	docker-compose exec -T fpm /opt/bin/php /opt/tests/test_5_fpm_invocation.php
	docker-compose down

test_function_zip:
	docker-compose build zip-function && docker-compose run --rm zip-function

test_manual_extensions:
	docker-compose run \
		--rm \
		--entrypoint /opt/bin/php \
		-e PHP_INI_SCAN_DIR="/opt/php-ini/:/opt/tests/" \
		isolation /opt/tests/test_6_manual_enabling_extensions.php

ssh_extensions:
	docker-compose run --rm --entrypoint /bin/bash extensions

ssh_isolation:
	docker-compose run -e PHP_INI_SCAN_DIR="/opt/php-ini/:/opt/tests/" --rm --entrypoint /bin/bash isolation

SHELL := /bin/bash

.PHONY: layers
# Publish the layers on AWS Lambda
publish: layers
	php publish.php

# Build the layers
layers: export/console.zip export/php-72.zip export/php-73.zip export/php-72-fpm.zip export/php-73-fpm.zip

# The PHP runtimes
export/php%.zip: build
	PHP_VERSION=$$(echo $@| tail -c +8|head -c -5);\
	rm -f $@;\
	mkdir export/tmp ; cd export/tmp ;\
	docker run --entrypoint "tar" bref/$$PHP_VERSION:latest -ch -C /opt .  |tar -x;zip --quiet --recurse-paths ../$$PHP_VERSION.zip . ;
	rm -rf export/tmp

# The console runtime
export/console.zip: layers/console/bootstrap
	rm -f export/console.zip
	cd layers/console && zip ../../export/console.zip bootstrap

# Build the docker container that will be used to compile PHP and its extensions
compiler: compiler.Dockerfile
	docker build -f ${PWD}/compiler.Dockerfile -t bref/runtime/compiler:latest .

# Compile PHP and its extensions
build: compiler
	docker build -f ${PWD}/php-intermediary.Dockerfile -t bref/php-72-intermediary:latest $(shell helpers/docker_args.sh versions.ini php72) .
	cd layers/fpm ; docker build -t bref/php-72-fpm:latest --build-arg LAYER_IMAGE=bref/php-72-intermediary:latest . ; cd ../..
	cd layers/fpm-dev ; docker build -t bref/php-72-fpm-dev:latest --build-arg LAYER_IMAGE=bref/php-72-intermediary:latest . ; cd ../..
	cd layers/function ; docker build -t bref/php-72:latest --build-arg LAYER_IMAGE=bref/php-72-intermediary:latest . ; cd ../..
	docker build -f ${PWD}/php-intermediary.Dockerfile -t bref/php-73-intermediary:latest $(shell helpers/docker_args.sh versions.ini php73) .
	cd layers/fpm ; docker build -t bref/php-73-fpm:latest --build-arg LAYER_IMAGE=bref/php-73-intermediary:latest . ; cd ../..
	cd layers/fpm-dev ; docker build -t bref/php-73-fpm-dev:latest --build-arg LAYER_IMAGE=bref/php-73-intermediary:latest . ; cd ../..
	cd layers/function ; docker build -t bref/php-73:latest --build-arg LAYER_IMAGE=bref/php-73-intermediary:latest . ; cd ../..
	cd layers/web; docker build -t bref/web:latest . ; cd ../..

publish: build
	docker push bref/php-72:latest
	docker push bref/php-73:latest

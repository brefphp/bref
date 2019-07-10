SHELL := /bin/bash

# Publish the layers on AWS Lambda
publish: layers
	php publish.php

# Build the layers
layers: export/console.zip export/php-%.zip

# The PHP runtimes
export/php-%.zip: distribution

# The console runtime
export/console.zip: layers/console/bootstrap
	rm -f export/console.zip
	cd console && zip ../export/console.zip bootstrap

# Build the docker container that will be used to compile PHP and its extensions
compiler: compiler.Dockerfile
	docker build -f ${PWD}/compiler.Dockerfile -t bref/runtime/compiler:latest .

# Compile PHP and its extensions
build: compiler
	docker build -f ${PWD}/php-intermediary.Dockerfile -t bref/php-72:latest $(shell helpers/docker_args.sh versions.ini php72) .
	docker build -f ${PWD}/layers/fpm-dev/Dockerfile -t bref/php-72-fpm-dev:latest --build-arg LAYER_IMAGE=bref/php-72:latest .
	docker build -f ${PWD}/php-intermediary.Dockerfile -t bref/php-73:latest $(shell helpers/docker_args.sh versions.ini php73) .
	docker build -f ${PWD}/layers/fpm-dev/Dockerfile -t bref/php-73-fpm-dev:latest --build-arg LAYER_IMAGE=bref/php-73:latest .

# Export the compiled PHP artifacts into zip files that can be uploaded as Lambda layers
distribution: build
	# Run the export script for PHP 7.2
	docker run --rm \
		--env PHP_SHORT_VERSION=72 \
		--volume ${PWD}/layers:/layers:ro \
		--volume ${PWD}/export:/export \
		--volume ${PWD}/export.sh:/export.sh:ro \
		bref/php-72:latest \
		/export.sh
	# Run the export script for PHP 7.3
	docker run --rm \
		--env PHP_SHORT_VERSION=73 \
		--volume ${PWD}/layers:/layers:ro \
		--volume ${PWD}/export:/export \
		--volume ${PWD}/export.sh:/export.sh:ro \
		bref/php-73:latest \
		/export.sh

publish: build
	docker push bref/php-72:latest
	docker push bref/php-73:latest

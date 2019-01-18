SHELL := /bin/bash

# Build the docker container that will be used to compile PHP and its extensions
compiler:
	docker build -f ${PWD}/compiler.Dockerfile -t bref/runtime/compiler:latest .

# Compile PHP and its extensions
build: compiler
	docker build -f ${PWD}/php.Dockerfile  -t bref/runtime/php:latest $(shell helpers/docker_args.php php72) .

# Export the compiled PHP artifacts into zip files that can be uploaded as Lambda layers
distribution: build
	rm -rf export/
	docker build --no-cache -f ${PWD}/export.Dockerfile -t bref/runtime/dist:latest .
	docker run --name bref-export-container bref/runtime/dist:latest /bin/true
	docker cp  bref-export-container:/export ..
	docker rm  bref-export-container

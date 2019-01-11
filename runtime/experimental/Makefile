SHELL := /bin/bash

php: build publish

compiler:
	docker build -f ${PWD}/compiler.Dockerfile -t bref/runtime/compiler:latest .

build: compiler
	docker build -f ${PWD}/php.Dockerfile  -t bref/runtime/php:latest $(shell helpers/docker_args.php php72) .

distribution:
	rm -rf export/
	docker build --no-cache -f ${PWD}/export.Dockerfile -t bref/runtime/dist:latest .
	docker run --name bref-export-container bref/runtime/dist:latest /bin/true
	docker cp  bref-export-container:/export .
	docker rm  bref-export-container

publish: distribution
	php publish.php

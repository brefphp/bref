SHELL := /bin/bash

REGION ?= us-east-2
BREF_VERSION ?= 0.3.0
PHP_VERSION_SHORT ?= 7.2
PHP_VERSION_SHORT_UNDERSCORE = $(subst .,_,${PHP_VERSION_SHORT})

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
	$(eval LAYER_NAME = bref-php-default-${PHP_VERSION_SHORT_UNDERSCORE})

	# Publish the layer
	$(eval LAYER_VERSION = $(shell aws lambda publish-layer-version --region=${REGION} --layer-name ${LAYER_NAME} --description "bref PHP ${PHP_VERSION} Default" --license-info "MIT" --zip-file fileb://export/php-${PHP_VERSION_SHORT}-cli.zip --compatible-runtimes provided --output text --query Version))

	# Add layer permissions
	$(eval RESOURCE = $(shell aws lambda add-layer-version-permission \
		--region=${REGION} \
		--layer-name ${LAYER_NAME} \
		--version-number ${LAYER_VERSION} \
		--statement-id=public \
		--action lambda:GetLayerVersion \
		--principal '*' | python2 -c "import sys, json; print json.load(sys.stdin)['Statement']" | python2 -c "import sys, json; print json.load(sys.stdin)['Resource']"))

	echo "Done! Published ${RESOURCE}"

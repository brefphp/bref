SHELL := /bin/bash

REGION ?= us-east-1
BREF_VERSION ?= 0.3.0
PHP_VERSION_SHORT ?= 7.3
S3_BUCKET ?= bref-php-${REGION}
PHP_VERSION_SHORT_UNDERSCORE = $(subst .,_,${PHP_VERSION_SHORT})


compiler:
	docker build -f ${PWD}/compiler.Dockerfile -t bref/runtime/compiler:latest .

php73: make/php73 distro

make/php73: compiler
	docker build -f ${PWD}/php.Dockerfile  -t bref/runtime/php:latest $(shell helpers/docker_args.php php73) .

publish/${PHP_VERSION_SHORT}/default:
	$(eval LAYER_NAME = bref-php-default-${PHP_VERSION_SHORT_UNDERSCORE})
	$(eval S3_KEY = ${BREF_VERSION}/php-default/${PHP_VERSION_SHORT}/php.zip)

	# Upload to S3
	aws s3 cp export/php-${PHP_VERSION_SHORT}-cli.zip s3://${S3_BUCKET}/${S3_KEY}

	# Publish the layer
	$(eval LAYER_VERSION = $(shell aws lambda publish-layer-version --region=${REGION} --layer-name ${LAYER_NAME} --description "bref PHP ${PHP_VERSION} Default" --license-info "MIT" --content S3Bucket=${S3_BUCKET},S3Key=${S3_KEY} --compatible-runtimes provided --output text --query Version))

	# Add layer permissions
	$(eval RESOURCE = $(shell aws lambda add-layer-version-permission \
		--region=${REGION} \
		--layer-name ${LAYER_NAME} \
		--version-number ${LAYER_VERSION} \
		--statement-id=public \
		--action lambda:GetLayerVersion \
		--principal '*' | python2 -c "import sys, json; print json.load(sys.stdin)['Statement']" | python2 -c "import sys, json; print json.load(sys.stdin)['Resource']"))

	echo "Done! Published ${RESOURCE}"

php72: make_php72 distro

make_php72: compiler
	docker build -f ${PWD}/php.Dockerfile -t bref/runtime/php:latest $(shell helpers/docker_args.php php72) .

distro_php73:
	docker build -f ${PWD}/export.Dockerfile -t bref/runtime/dist:latest .
	docker run --name bref-export-container bref/runtime/dist:latest /bin/true
	docker cp  bref-export-container:/export .
	docker rm  bref-export-container




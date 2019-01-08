SHELL := /bin/bash

compiler:
	docker build -f ${PWD}/compiler.Dockerfile -t bref/runtime/compiler:latest .

php73: make_php73

make_php73:
	docker build -f ${PWD}/php.Dockerfile  -t bref/runtime/php:latest $(shell helpers/docker_args.php php73) .

php72: make_php72

make_php72:
	docker build -f ${PWD}/php.Dockerfile -t bref/runtime/php:latest $(shell helpers/docker_args.php php72) .

php71: make_php71

make_php71:
		docker build -f ${PWD}/php.Dockerfile -t bref/runtime/php:latest $(shell helpers/docker_args.php php71) .

distro:
	docker build -f ${PWD}/export.Dockerfile -t bref/runtime/dist:latest .

export: distro
	mkdir -p ${PWD}/exports
	docker run --interactive --tty -v ${PWD}/exports:/export -e "TERM=xterm-256color" bref/runtime/dist:latest
	ls -al ${PWD}/exports

publish/php73:
	# Upload php.zip to S3
	aws s3 cp exports/php-default-7.3*.zip s3://${S3_BUCKET}/php-default-7.3.zip
	aws s3 cp exports/php-fpm-7.3*.zip s3://${S3_BUCKET}/php-fpm-7.3.zip

	# Publish the layer
	DEFAULT_LAYER_VERSION=$(shell aws lambda publish-layer-version --region=${REGION} --layer-name php-7.3-default --description "PHP 7.3 Default" --license-info "MIT" --content S3Bucket=${S3_BUCKET},S3Key=php-default-7.3.zip --compatible-runtimes provided --output text --query Version)
	FPM_LAYER_VERSION=$(shell aws lambda publish-layer-version --region=${REGION} --layer-name php-7.3-fpm --description "PHP 7.3 FPM" --license-info "MIT" --content S3Bucket=${S3_BUCKET},S3Key=php-fpm-7.3.zip --compatible-runtimes provided --output text --query Version)

	# Add layer permissions
	aws lambda add-layer-version-permission --region=${REGION} --layer-name php-7.3-default --version-number ${DEFAULT_LAYER_VERSION} --statement-id=public --action lambda:GetLayerVersion --principal '*'
	aws lambda add-layer-version-permission --region=${REGION} --layer-name php-7.3-fpm --version-number ${FPM_LAYER_VERSION} --statement-id=public --action lambda:GetLayerVersion --principal '*'
	echo "Published php-7.3-default version ${DEFAULT_LAYER_VERSION}!"
	echo "Published php-7.3-fpm version ${FPM_LAYER_VERSION}!"

publish/php72:
	# Upload php.zip to S3
	aws s3 cp exports/php-default-7.2*.zip s3://${S3_BUCKET}/php-default-7.2.zip
	aws s3 cp exports/php-fpm-7.2*.zip s3://${S3_BUCKET}/php-fpm-7.2.zip

	# Publish the layer
	DEFAULT_LAYER_VERSION=$(shell aws lambda publish-layer-version --region=${REGION} --layer-name php-7.2-default --description "PHP 7.2 Default" --license-info "MIT" --content S3Bucket=${S3_BUCKET},S3Key=php-default-7.2.zip --compatible-runtimes provided --output text --query Version)
	FPM_LAYER_VERSION=$(shell aws lambda publish-layer-version --region=${REGION} --layer-name php-7.2-fpm --description "PHP 7.2 FPM" --license-info "MIT" --content S3Bucket=${S3_BUCKET},S3Key=php-fpm-7.2.zip --compatible-runtimes provided --output text --query Version)

	# Add layer permissions
	aws lambda add-layer-version-permission --region=${REGION} --layer-name php-7.2-default --version-number ${DEFAULT_LAYER_VERSION} --statement-id=public --action lambda:GetLayerVersion --principal '*'
	aws lambda add-layer-version-permission --region=${REGION} --layer-name php-7.2-fpm --version-number ${FPM_LAYER_VERSION} --statement-id=public --action lambda:GetLayerVersion --principal '*'
	echo "Published php-7.2-default version ${DEFAULT_LAYER_VERSION}!"
	echo "Published php-7.2-fpm version ${FPM_LAYER_VERSION}!"

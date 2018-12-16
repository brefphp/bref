#!/bin/sh

set -e

# TODO dynamically change the region
REGION=us-east-2
BREF_VERSION=0.3
PHP_VERSION=7.2.5
LAYER_NAME=php-72-fpm
# TODO dynamically change the region
S3_BUCKET=bref-php-us-east-2
S3_KEY=$BREF_VERSION/php-fpm/$PHP_VERSION/php.zip

# Clean previous build
rm -f php.zip

# Build php.zip
docker run --rm --env PHP_VERSION=$PHP_VERSION --volume $PWD:/export lambci/lambda:build-nodejs8.10 /export/build.sh

# Upload php.zip to S3
aws s3 cp php.zip s3://$S3_BUCKET/$S3_KEY

# Publish the layer
LAYER_VERSION=$(aws lambda publish-layer-version --region=$REGION --layer-name $LAYER_NAME --description "PHP $PHP_VERSION" --license-info "MIT" --content S3Bucket=$S3_BUCKET,S3Key=$S3_KEY --compatible-runtimes provided --output text --query Version)

# Add layer permissions
aws lambda add-layer-version-permission --region=$REGION --layer-name $LAYER_NAME --version-number $LAYER_VERSION --statement-id=public --action lambda:GetLayerVersion --principal '*'

echo "Done! Published version $LAYER_VERSION!"

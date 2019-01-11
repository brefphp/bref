#!/bin/sh

# Publish a layer

set -e

PHP_VERSION=7.2

if [[ -z "$REGION" ]] ; then
    echo 'The region must be passed in the REGION environment variable'
    exit 1
fi
if [[ -z "$LAYER_NAME" ]] ; then
    echo 'The layer name must be passed in the LAYER_NAME environment variable'
    exit 1
fi
if [[ -z "$FILE_NAME" ]] ; then
    echo 'The layer name must be passed in the FILE_NAME environment variable'
    exit 1
fi

# Publish the layer
LAYER_VERSION=$(aws lambda publish-layer-version \
    --region=$REGION \
    --layer-name $LAYER_NAME \
    --description "PHP $PHP_VERSION" \
    --license-info "MIT" \
    --zip-file fileb://$FILE_NAME \
    --compatible-runtimes provided \
    --output text \
    --query Version)

# Add layer permissions
aws lambda add-layer-version-permission \
    --region=$REGION \
    --layer-name $LAYER_NAME \
    --version-number $LAYER_VERSION \
    --statement-id=public \
    --action lambda:GetLayerVersion \
    --principal '*'

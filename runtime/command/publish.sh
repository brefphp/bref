#!/bin/sh

set -e

if [[ -z "$REGION" ]] ; then
    echo 'The region must be passed in the REGION environment variable'
    exit 1
fi

LAYER_NAME=command
S3_BUCKET=bref-php-$REGION
S3_KEY=$BREF_VERSION/command/layer.zip

# Clean previous build
rm -f layer.zip

# Build layer.zip
zip layer.zip bootstrap

# Upload layer.zip to S3
aws s3 cp layer.zip s3://$S3_BUCKET/$S3_KEY

# Publish the layer
LAYER_VERSION=$(aws lambda publish-layer-version --region=$REGION --layer-name $LAYER_NAME --description "Command executor" --license-info "MIT" --content S3Bucket=$S3_BUCKET,S3Key=$S3_KEY --compatible-runtimes provided --output text --query Version)

# Add layer permissions
aws lambda add-layer-version-permission --region=$REGION --layer-name $LAYER_NAME --version-number $LAYER_VERSION --statement-id=public --action lambda:GetLayerVersion --principal '*'

echo "Done! Published $LAYER_NAME version $LAYER_VERSION!"

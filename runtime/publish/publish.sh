#!/bin/bash

echo "[Publish] Publishing layer..."

VERSION=$(aws lambda publish-layer-version \
   --region ${REGION} \
   --layer-name ${ARCHITECTURE}-${PHP_VERSION}-${TYPE} \
   --description "description here" \
   --license-info MIT \
   --zip-file fileb:///tmp/bref-zip/${TYPE}/${PHP_VERSION}-${TYPE}-layer.zip \
   --compatible-runtimes provided.al2 \
   --output text \
   --query Version)

echo "[Publish] Layer ${VERSION} published! Adding layer permission..."

aws lambda add-layer-version-permission \
    --region ${REGION} \
    --layer-name ${ARCHITECTURE}-${PHP_VERSION}-${TYPE} \
    --version-number ${VERSION} \
    --statement-id public \
    --action lambda:GetLayerVersion \
    --principal *

echo "[Publish] Permission added!"
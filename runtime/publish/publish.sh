#!/bin/bash

echo "[Publish] Publishing layer..."

VERSION=$(aws lambda publish-layer-version \
   --region ${REGION} \
   --layer-name ${LAYER} \
   --description "description here" \
   --license-info MIT \
   --zip-file fileb:///tmp/bref-zip/${LAYER}-layer.zip \
   --compatible-runtimes provided.al2 \
   --output text \
   --query Version)

echo "[Publish] Layer ${VERSION} published! Adding layer permission..."

aws lambda add-layer-version-permission \
    --region ${REGION} \
    --layer-name ${LAYER} \
    --version-number ${VERSION} \
    --statement-id public \
    --action lambda:GetLayerVersion \
    --principal *

echo "[Publish] Permission added!"
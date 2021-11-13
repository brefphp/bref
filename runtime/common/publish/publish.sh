#!/bin/bash

set -e

echo "[Publish] Publishing layer..."

VERSION=$(aws lambda publish-layer-version \
   --region ${REGION} \
   --layer-name prototype-${CPU}-${PHP_VERSION}-${TYPE} \
   --description "Bref Runtime for ${PHP_VERSION}" \
   --license-info MIT \
   --zip-file fileb:///tmp/bref-zip/${PHP_VERSION}-${TYPE}.zip \
   --compatible-runtimes provided.al2 \
   --output text \
   --query Version)

echo "[Publish] Layer ${VERSION} published! Adding layer permission..."

aws lambda add-layer-version-permission \
    --region ${REGION} \
    --layer-name prototype-${CPU}-${PHP_VERSION}-${TYPE} \
    --version-number ${VERSION} \
    --statement-id public \
    --action lambda:GetLayerVersion \
    --principal *

echo "[Publish] Permission added!"
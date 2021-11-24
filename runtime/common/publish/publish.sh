#!/bin/bash

set -xe

LAYER_NAME="prototype-${CPU}-${PHP_VERSION}-${TYPE}"

echo "[Publish] Publishing layer ${LAYER_NAME}..."

VERSION=$(aws lambda publish-layer-version \
   --region ${REGION} \
   --layer-name ${LAYER_NAME} \
   --description "Bref Runtime for ${PHP_VERSION}" \
   --license-info MIT \
   --zip-file fileb:///tmp/bref-zip/${PHP_VERSION}-${TYPE}.zip \
   --compatible-runtimes provided.al2 \
   --output text \
   --query Version)

echo "[Publish] Layer ${VERSION} published! Adding layer permission..."

aws lambda add-layer-version-permission \
    --region ${REGION} \
    --layer-name ${LAYER_NAME} \
    --version-number ${VERSION} \
    --statement-id public \
    --action lambda:GetLayerVersion \
    --principal "*" \
    --output text \
    --query Statement

echo "[Publish] Layer ${LAYER} added!"

# This file will be used by runtime/common/utils/parse-output-into-layers-json.php
# Here we will keep a mapping in the format of LAYER[REGION]="LAYER:VERSION" so
# that the Serverless Plugin can resolve the Lambda Version at deployment time.
# See https://bref.sh/docs/environment/serverless-yml.html#plugins
echo "${LAYER_NAME}[${REGION}]=${LAYER_NAME}:${VERSION}" >> /tmp/bref-zip/output.ini
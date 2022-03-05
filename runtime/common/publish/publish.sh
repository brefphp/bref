#!/bin/bash

# This file is responsible for publishing a new layer on AWS using the AWS CLI. We rely on AWS CLI
# because it comes installed by default on AWS CodeBuild.
# The LAYER NAME must be specified when invoking this script. That is done on ./Makefile
# After publishing a new Layer, we retrieve the layer version and write a .ini file with the
# Layer Name and it's version. We use ini file as it is safe to have parallel processes
# appending new lines to the end of the file without needing a file lock system.

set -e

if [ -z "${LAYER_NAME}" ]
then
      echo "\$LAYER_NAME must be specified"
      exit 1
fi

echo "[Publish] Publishing layer ${LAYER_NAME}..."

VERSION=$(aws lambda publish-layer-version \
   --region ${REGION} \
   --layer-name "prototype-${LAYER_NAME}" \
   --description "Bref Runtime" \
   --license-info MIT \
   --zip-file fileb:///tmp/bref-zip/${LAYER_NAME}.zip \
   --compatible-runtimes provided.al2 \
   --output text \
   --query Version)

echo "[Publish] Layer ${VERSION} published! Adding layer permission..."

aws lambda add-layer-version-permission \
    --region ${REGION} \
    --layer-name "prototype-${LAYER_NAME}" \
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
echo "${LAYER_NAME}[${REGION}]=prototype-${LAYER_NAME}:${VERSION}" >> /tmp/bref-zip/output.ini

deploy: package upload
package:
	sam package \
        --template-file template.yaml \
        --output-template-file .cloudformation.yaml \
        --s3-bucket ${S3_BUCKET}
upload:
	sam deploy \
        --template-file .cloudformation.yaml \
        --stack-name bref-default-php72 \
        --capabilities CAPABILITY_IAM \
        --region ${REGION}

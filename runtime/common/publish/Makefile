
publish-by-type:
	# Add executable permission to the publish script
	chmod +x ./publish.sh

	LAYER_NAME=prototype-${CPU}-${PHP_VERSION}-${TYPE} $(MAKE) -j7 parallel-publish

##########################################################################################

# This command is designed for parallel execution of layer publishing.
# When we do `make publish -j7`, make will execute each command defined
# here in parallel.
parallel-publish: america-1 america-2 europe-1 europe-2 asia-1 asia-2 miscellaneous

america-1:
	REGION=us-east-1 ./publish.sh #US East (N. Virginia)
	REGION=us-east-2 ./publish.sh #US East (Ohio)
	REGION=us-west-1 ./publish.sh #US West (N. California)

america-2:
	REGION=us-west-2 ./publish.sh #US West (Oregon)
	REGION=ca-central-1 ./publish.sh #Canada (Central)
	REGION=sa-east-1 ./publish.sh #South America (São Paulo)

europe-1:
	REGION=eu-west-1 ./publish.sh #Europe (Ireland)
	REGION=eu-west-2 ./publish.sh #Europe (London)
	REGION=eu-west-3 ./publish.sh #Europe (Paris)

europe-2:
	REGION=eu-north-1 ./publish.sh #Europe (Stockholm)
	REGION=eu-south-1 ./publish.sh #Europe (Milan)
	REGION=eu-central-1 ./publish.sh #Europe (Frankfurt)

asia-1:
	REGION=ap-east-1 ./publish.sh #Asia Pacific (Hong Kong)
	REGION=ap-south-1 ./publish.sh #Asia Pacific (Mumbai)
	REGION=ap-southeast-1 ./publish.sh #Asia Pacific (Singapore)

asia-2:
	REGION=ap-northeast-1 ./publish.sh #Asia Pacific (Tokyo)
	REGION=ap-northeast-3 ./publish.sh #Asia Pacific (Osaka)
	REGION=ap-northeast-2 ./publish.sh #Asia Pacific (Seoul)

miscellaneous:
	REGION=af-south-1 ./publish.sh #Africa (Cape Town)
	REGION=me-south-1 ./publish.sh #Middle East (Bahrain)
	REGION=ap-southeast-2 ./publish.sh #Asia Pacific (Sydney)
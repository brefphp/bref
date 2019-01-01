# Deployment

Bref recommends to use SAM to deploy your serverless application.

While you can read [SAM's official deployment documentation](https://github.com/awslabs/aws-sam-cli/blob/develop/docs/deploying_serverless_applications.rst) the following guide is optimized for PHP projects.

## How it works

Let's look at how serveless applications are deployed on AWS with SAM.

### Stacks

Everything is deployed through a **[CloudFormation](https://aws.amazon.com/cloudformation/) stack**. A stack is nothing more than a bunch of things that compose an application:

- lambda functions
- S3 buckets
- databases
- etc.

Stacks make it easy to group those resources together: the whole stack is updated at once on deployments, and if you delete the stack all the resources inside are deleted together too. Clean and simple.

All of this is great except CloudFormation configuration is complex. This is where SAM helps.

### SAM

SAM offers a simpler configuration format. This is what you are using if you use Bref (the `template.yaml` file).

The deployment process with SAM works like this:

- upload the application code to a S3 bucket
- generate a temporary CloudFormation config (YAML file) that references the uploaded code
- deploy the CloudFormation stack

## Deploying with SAM

### Setup

> This step must be done only once per application.

To be deployed into AWS Lambda, your code must be uploaded on AWS S3.

That means you must **create a S3 bucket** to host your application code:

```sh
aws s3 mb s3://<bucket-name>
```

The content of this bucket will be managed by AWS SAM. Do not use it in your application to store things like assets, uploaded files, etc.

### Deployment

Before deploying make sure your code is ready to be deployed. For example remove any development files from the project and install Composer dependencies optimized for production:

```bash
composer install --optimize-autoloader --no-dev
```

> If you run this command in your local installation this might break your development setup (it will remove dev dependencies). Ideally deployment should be done in a separate directory, from scratch.

**Step1**, upload the code and generate the stack configuration:

```bash
sam package \
    --output-template-file .stack.yaml \
    --s3-bucket <bucket-name>
```

> `<bucket-name>` is the name of the bucket you created in [Setup](#setup).

The `.stack.yaml` file describes the stack and references the code that was just uploaded to S3. You can add it to `.gitignore` and remove it once you have finished deploying.

**Step2**, deploy the generated stack:

```bash
sam deploy \
    --template-file .stack.yaml \
    --capabilities CAPABILITY_IAM \
    --stack-name <stack-name>
```

> `<stack-name>` can be the name of your project (made of letters, numbers and `-`).

While you wait for your stack to be created you can check out [the CloudFormation dashboard](https://us-east-2.console.aws.amazon.com/cloudformation/home). You will see your stack appear there. In case of an error, click on your stack and check out the *Events* tab to see what went wrong.

## Automating deployments

Deploying from your machine is not perfect:

- it will deploy development dependencies from Composer
- it will deploy development configuration
- it will deploy all the files in the project's directory, even those in `.gitignore`

This works fine when testing, but for a production setup it is better to automate deployments.

If you are using Gitlab CI, Travis CI, CircleCI or any tool of the sort you will want to automate the deployment to something like this:

```bash
# Install Composer dependencies optimized for production
composer install --optimize-autoloader --no-dev

# Perform extra tasks for your framework of choice
# (e.g. generate the framework cache, etc.)
# [...]

# Package
sam package ...
# Deploy
sam deploy ...
```

That will also mean creating AWS access keys so that the continuous integration is allowed to deploy.

## Deletion

Since CloudFormation is used to deploy the application as a "stack", deleting the application can be done by deleting the stack.

Remember to also delete the S3 bucket that was created to hold the application's code (it is not inside the CloudFormation stack).

## Learn more

Read more about `sam deploy` in [the official documentation](https://github.com/awslabs/aws-sam-cli/blob/develop/docs/deploying_serverless_applications.rst).

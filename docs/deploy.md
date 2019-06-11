---
title: Deployment
currentMenu: deploy
---

Bref recommends to use [the Serverless framework](https://serverless.com/) to deploy your serverless application.

While you can read [the official deployment documentation](https://serverless.com/framework/docs/providers/aws/guide/deploying/) the following guide is optimized for PHP projects.

## How it works

### Stacks

Everything is deployed through a **[CloudFormation](https://aws.amazon.com/cloudformation/) stack**. A stack is nothing more than a bunch of things that compose an application:

- lambda functions
- S3 buckets
- databases

Stacks make it easy to group those resources together: the whole stack is updated at once on deployments, and if you delete the stack all the resources inside are deleted together too. Clean and simple.

All of this is great except CloudFormation configuration is complex. This is where *Serverless* helps.

### `serverless.yml`

The *Serverless* framework offers a simple configuration format. This is what you are using if you use Bref. That configuration is written in your project in a `serverless.yml` file.

## Deploying with Serverless

### Deployment

Before deploying make sure your code is ready to be deployed. For example remove any development files from the project and install Composer dependencies optimized for production:

```bash
composer install --optimize-autoloader --no-dev
```

> If you run this command in your local installation this might break your development setup (it will remove dev dependencies). Ideally deployment should be done in a separate directory, from scratch.

Once your project is ready, you can deploy via the following command:

```bash
serverless deploy
```

A `.serverless/` directory will be created. You can add it to `.gitignore`.

While you wait for your stack to be created you can check out [the CloudFormation dashboard](https://console.aws.amazon.com/cloudformation/home). Your stack will appear there.

If an error occurs, the root cause will be displayed in the CLI output.

## Automating deployments

Deploying from your machine is not perfect:

- it will deploy development dependencies from Composer
- it will deploy development configuration
- it will deploy all the files in the project directory, even those in `.gitignore`

This works fine when testing, but for a production setup it is better to automate deployments.

If you are using Gitlab CI, Travis CI, CircleCI or any tool of the sort you will want to automate the deployment to something like this:

```bash
# Install Composer dependencies optimized for production
composer install --optimize-autoloader --no-dev

# Perform extra tasks for your framework of choice
# (e.g. generate the framework cache)
# [...]

# Deploy
serverless deploy
```

That will also mean creating AWS access keys so that the continuous integration is allowed to deploy.

## Deletion

To delete the whole application you can run:

```bash
serverless remove
```

## Learn more

Read more about `serverless deploy` in [the official documentation](https://serverless.com/framework/docs/providers/aws/guide/deploying/).

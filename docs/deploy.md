---
title: Deployment
current_menu: deploy
---

Bref recommends using [the Serverless framework](https://serverless.com/) to deploy your serverless application. This page will show you how.

## First deployment

Deploy your application on your AWS account by running:

```bash
serverless deploy
```

🎉 congrats on creating your first serverless application!

> A `.serverless/` directory will be created. You can add it to `.gitignore`.
>
> Want to get an overview of your deployed application? Launch the Bref Dashboard via the `vendor/bin/bref dashboard` command.

## Deploying for production

In the previous step, we deployed the project installed on your machine. This is probably a *development version*.

For production, we usually don't want to deploy:

- dev dependencies
- dev configuration
- etc.

Instead, let's remove development dependencies and optimize Composer's autoloader for production:

```bash
composer install --prefer-dist --optimize-autoloader --no-dev
```

Now is also the best time to configure your project for production, as well as build any file cache if necessary.

Once your project is ready, you can deploy via the following command:

```bash
serverless deploy
```

## Automating deployments

If you are using Gitlab CI, Travis CI, CircleCI or any tool of the sort you will want to automate the deployment to something like this:

```bash
# Install Composer dependencies optimized for production
composer install --prefer-dist --optimize-autoloader --no-dev

# Perform extra tasks for your framework of choice
# (e.g. generate the framework cache)
# [...]

# Deploy
serverless deploy
```

That will also mean creating AWS access keys so that the continuous integration is allowed to deploy.

You can find configuration examples for CI/CD tools in the [Bref examples repository](https://github.com/brefphp/examples).

## Regions

AWS runs applications in different [regions](https://aws.amazon.com/about-aws/global-infrastructure/). The default region is `us-east-1` (North Virginia, USA).

If you want to use a different region (for example to host your application closer to your visitors) you can configure it in your `serverless.yml`:

```yaml
provider:
    region: eu-west-1 # Ireland, Europe
    ...
```

> If you are a first time user, using the `us-east-1` region (the default region) is recommended for the first projects. It simplifies commands and avoids a lot of mistakes when discovering AWS.

## Deletion

To delete the whole application you can run:

```bash
serverless remove
```

## How it works

### CloudFormation stacks

The `serverless deploy` command will deploy everything via a **[CloudFormation](https://aws.amazon.com/cloudformation/) stack**. A stack is nothing more than a bunch of things that compose an application:

- lambda functions
- S3 buckets
- databases

Stacks make it easy to group those resources together: the whole stack is updated at once on deployments, and if you delete the stack all the resources inside are deleted together too. Clean and simple.

All of this is great except CloudFormation configuration is complex. This is where *Serverless* helps.

### `serverless.yml`

The *Serverless* framework offers a simple configuration format. This is what you are using if you use Bref. That configuration is written in your project in a `serverless.yml` file.

You can [learn more about that configuration format here](environment/serverless-yml.md).

## Learn more

Read more about `serverless deploy` in [the official documentation](https://serverless.com/framework/docs/providers/aws/guide/deploying/).

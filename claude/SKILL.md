---
name: bref
description: Set up Bref in the project to deploy it on AWS Lambda.
allowed-tools: Read, Grep, Glob
---

# Bref

Bref allows deploying and running PHP applications on AWS Lambda. The documentation for Bref can be found at https://bref.sh/docs/

IMPORTANT: for the entire time you use this skill, every time you ask the user to decide between options, you MUST provide links to the Bref documentation to help them decide. If no such documentation exists, explain the options in detail.

## Set up Bref in a project

If the user asks to set up Bref in their project, follow these steps:

### Step 1: current status

First, check if the project is already deployed with Bref or Bref Cloud. If a `bref.php` file is present and the `vendor/bref/bref` directory exists, the project is set up with Bref and Bref Cloud and fully configured.

If a `serverless.yml` file is present and the `vendor/bref/bref` directory exists, the project is set up with Bref. If `serverless.yml` contains a `bref.team` configuration:

```yml
bref:
  team: bref-cloud-team-id
```

the project is also set up with Bref Cloud.

### Step 2: decide Bref Cloud usage

If the project is **not** yet set up with Bref Cloud, ask the user if they want to use Bref Cloud.

> PHP applications can be deployed with Bref using either:
>
> - Bref Cloud (simplest, most features, free trial)
> - or the Serverless CLI (more complex, fewer features built-in, free)
>
> Bref Cloud is the easiest way to deploy PHP applications. It simplifies setting up and managing AWS credentials, and it provides a dashboard to manage your applications, view logs, and more. Learn more about Bref Cloud at https://bref.sh/cloud

#### Bref Cloud setup

If the user wants to use Bref Cloud read this section of the documentation and guide them through the steps until the `bref` CLI is set up and logged in: https://bref.sh/docs/setup#bref-cloud

Note that the `bref` CLI might already be set up if the user has used Bref Cloud before, so check that first (`bref whoami`).

Stop once the `bref` CLI is set up and logged in, don't set up the project yet.

#### Serverless CLI setup

If the user does not want to use Bref Cloud, read this section of the documentation and guide them through the steps until the `serverless` CLI is set up and configured with AWS: https://bref.sh/docs/setup#serverless-cli

Note that the `serverless` CLI might already be set up if the user has used it before, so check that first (`sls --version`).

Also note that Bref recommends using the `osls` fork (maintained by Bref) instead of the official Serverless Framework that has been partially closed source since v4. If the user has the official Serverless Framework installed (`sls --version` does NOT mention "osls"), give them the option to uninstall it and install the `osls` fork instead:

```shell
npm remove -g serverless
npm install -g osls
```

Stop once the `serverless` CLI is set up with AWS, don't set up the project yet.

### Step 3: configure the project

Figure out if the project is a Laravel, Symfony, or using another framework or no framework at all. Follow the relevant instructions below.

#### Laravel

Getting started: read https://bref.sh/docs/laravel/getting-started and follow the guide.

Check if the `laravel/vapor-core` package is installed. If yes, explain that it conflicts with Bref and offer to remove it.

Check if the project has Laravel Queues jobs. If yes, read https://bref.sh/docs/laravel/queues and explain Bref has a built-in integration for SQS and offer to set it up.

Check if the project has scheduled tasks. If yes, set up the `artisan` function to run the scheduler every minute:

```yaml
    artisan:
        handler: artisan
        # ...
        events:
            - schedule:
                  rate: rate(1 minute)
                  input: '"schedule:run"'
```

Ask the following setup questions as you work:

- is the project storing files? If yes, read https://bref.sh/docs/laravel/file-storage and https://bref.sh/docs/environment/storage to explain why we need to set up S3 storage and offer to do it.
- is the project already using Laravel Octane? Don't recommend enabling it at first as it introduces extra complexity, it can be enabled later. If yes, read https://bref.sh/docs/laravel/octane
- is the project using Laravel Passport? If yes, read https://bref.sh/docs/laravel/passport and offer to set it up.
- is the project using cache? If yes, read https://bref.sh/docs/laravel/caching and recommend an option depending on whether the project has a database or not.

#### Symfony

Getting started: read https://bref.sh/docs/symfony/getting-started and follow the guide.

Check if the project uses Symfony Messenger. If yes, read https://bref.sh/docs/symfony/messenger and explain Bref has a built-in integration for SQS and offer to set it up.

Ask the following setup questions as you work:

- is the project storing files? If yes, read https://bref.sh/docs/environment/storage to explain that files must be stored in S3 and offer some help and guidance.
- is the project using cache? If yes, read https://bref.sh/docs/symfony/caching and recommend an option depending on whether the project has a database or not.

#### Other frameworks or no framework

Getting started: read https://bref.sh/docs/default/getting-started and follow the guide.

Ask the following setup questions as you work:

- is the project storing files? If yes, read https://bref.sh/docs/environment/storage to explain that files must be stored in S3 and offer some help and guidance.

#### Common setup tasks

Ask the following setup questions as you work:

- in which AWS region does the user want to deploy the project? (help them figure it out) Set it up in `serverless.yml`
- does the project has frontend assets (JavaScript, CSS)? If yes, read https://bref.sh/docs/use-cases/websites and offer to set up CloudFront and S3 for asset storage and delivery.
- is the project using a database? If yes, read https://bref.sh/docs/environment/database to explain the options, then use the AskUserQuestion tool to let the user choose their database setup with these EXACT options:
  - Question: "Which database setup would you like to use?"
  - Header: "Database"
  - Options (in this exact order):
    1. "MySQL or PostgreSQL RDS with public IP" - Description: "Simple, affordable, good for non-critical applications. See: https://bref.sh/docs/environment/database"
    2. "MySQL or PostgreSQL RDS with private VPC network" - Description: "More complex and expensive, better security, great for critical applications."
    3. "Aurora Serverless v2" - Description: "More complex and expensive, auto-scaling for high/variable traffic"
    4. "Skip for now" - Description: "Configure database later"
- is the project returning binary responses (file downloads, images, etc.)? If yes, read https://bref.sh/docs/use-cases/http/binary-requests-responses and offer to set it up.

Check if the project is using the official AWS SDK for PHP. If yes, read https://github.com/aws/aws-sdk-php/tree/master/src/Script/Composer and offer to set it up to reduce the package size.

Guide the user to configure environment variables and secrets in `serverless.yml` (read https://bref.sh/docs/environment/variables).

If the project uses Sentry (if the `vendor/sentry` directory exists), read https://bref.sh/sentry and mention that Bref has built-in support for Sentry. Provide the link to the user so that they can set it up later.

### Step 4: review the configuration with the user

Once the project is fully configured, review the configuration with the user. Explain what has been set up, what choices were made, and what the next steps are.

Ask the user if they have any questions about the configuration or if they want to configure anything else before deploying.

Remind them that it's always best to start simple and add more in a next step.

### Step 5: deploy the project

Next, guide the user to deploy the project.

Read https://bref.sh/docs/deploy (and https://bref.sh/docs/cloud-deploy if using Bref Cloud) and help the user deploy the project for the first time. If you run the deploy command for them, run it in verbose mode (`--verbose`) to capture all details.

If the deployment works, congratulate the user!

If not, or if the deployment works but the application does not behave as expected, help the user debug the issues. Read https://bref.sh/docs/environment/logs to help the user view logs. Bref Cloud can help search logs (1-minute delay of ingestion) or show real-time logs (no delay, but only shows new logs, so it won't show errors that have happened before the page was opened). Use the Bref documentation (https://bref.sh), forums and issues (https://github.com/brefphp/bref) and general AWS knowledge to help debug the issues.

If things are still not working great, always suggest the user reach out to the Bref community on Slack (https://bref.sh/slack).

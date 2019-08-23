---
title: What is Bref and serverless?
current_menu: what-is-bref
introduction: An introduction to what serverless and Bref can offer for PHP applications.
next:
    link: /docs/installation.html
    title: Installation
---

## Why serverless?

Serverless replaces the traditional approaches to running applications. With serverless:

- We don't manage, update, configure, provision servers or containers,
- We don't reserve or scale servers or containers, instead they are scaled automatically and transparently for us,
- We don't pay for fixed resources, instead we pay for what we actually use (e.g. execution time).

**Serverless can provide more scalable, affordable and reliable architectures for less effort.**

Serverless includes services like storage as a service, database as a service, message queue as a service, etc. One service in particular is interesting for us developers: *Function as a Service* (FaaS).

FaaS is a way to run code where the hosting provider takes care of setting up everything, keeping the application available 24/7, scaling it up and down and we are only charged *while the code is actually executing*.

## Why Bref?

<p class="text-xl">
Bref aims to make running PHP applications simple.
</p>

To reach that goal, Bref takes advantage of serverless technologies. However, while serverless is promising, there are many choices to make, tools to build and best practices to figure out.

Bref's approach is to:

- **simplify problems by removing choices**

    *instead of trying to address every need*
- **provide simple and familiar solutions**

    *instead of aiming for powerful custom solutions*
- **empower by sharing knowledge**

    *instead of hiding too much behind leaky abstractions*

### What is Bref

Bref (which means "brief" in french) comes as a Composer package and helps you deploy PHP applications to [AWS](https://aws.amazon.com) and run them on [AWS Lambda](https://aws.amazon.com/lambda/).

Bref provides:

- documentation
- PHP runtimes for AWS Lambda
- deployment tooling
- PHP frameworks integration

The choice of AWS as serverless provider is deliberate: at the moment AWS is the leading hosting provider, it is ahead in the serverless space in terms of features, performances and reliability.

Bref uses [the Serverless framework](https://serverless.com/) to configure and deploy serverless applications. Being the most popular tool, Serverless comes with a huge community, a lot of examples online and a simple configuration format.

## Use cases

Bref and AWS Lambda can be used to run many kind of PHP application, for example:

- APIs
- workers
- batch processes/scripts
- websites

Bref aims to support any PHP framework as well.

If you are interested in real-world examples as well as cost analyses head over to the [**Case Studies** page](case-studies.md).

## Maturity matrix

The matrix below provides an overview of the "maturity level" for common PHP applications.

This maturity level is a vague metric, however it can be useful to anticipate the effort and the limitations to expect for each scenario. While a green note doesn't mean that Bref and Lambda are silver bullets for the use case (there are no silver bullets), a red note doesn't mean this is impossible or advised against.

This matrix will be updated as Bref and AWS services evolve over time.

<table class="w-full text-xs sm:text-sm text-grey-darker mt-8 mb-5 table-fixed">
    <tr class="bg-grey-lightest">
        <th class="p-4"></th>
        <th class="font-normal p-4 border-b border-grey-light">Simplicity</th>
        <th class="font-normal p-4 border-b border-grey-light">Performances</th>
        <th class="font-normal p-4 border-b border-grey-light">Reliability</th>
    </tr>
    <tr class="border-b border-grey-lighter">
        <td class="p-4 bg-grey-lightest font-bold border-r border-grey-light">
            Jobs, Cron
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-orange-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-light"></span>
        </td>
    </tr>
    <tr class="border-b border-grey-lighter">
        <td class="p-4 bg-grey-lightest font-bold border-r border-grey-light">API</td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-light"></span>
        </td>
    </tr>
    <tr class="border-b border-grey-lighter">
        <td class="p-4 bg-grey-lightest font-bold border-r border-grey-light">API with MySQL / PostgreSQL</td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-red-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-orange-light"></span>
        </td>
    </tr>
    <tr class="border-b border-grey-lighter">
        <td class="p-4 bg-grey-lightest font-bold border-r border-grey-light">Website</td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-light"></span>
        </td>
    </tr>
    <tr class="border-b border-grey-lighter">
        <td class="p-4 bg-grey-lightest font-bold border-r border-grey-light">Website with MySQL / PostgreSQL</td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-orange-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-orange-light"></span>
        </td>
    </tr>
    <tr class="border-b border-grey-lighter">
        <td class="p-4 bg-grey-lightest font-bold border-r border-grey-light">Legacy application</td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-red-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-orange-light"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-red-light"></span>
        </td>
    </tr>
    <tr class="text-xs text-center leading-normal text-grey-dark">
        <td></td>
        <td class="p-3">
            Is this documented and simple to achieve?
        </td>
        <td class="p-3">
            Are performances acceptable?
        </td>
        <td class="p-3">
            Is this scenario production-ready?
        </td>
    </tr>
</table>

<div class="text-left text-xs text-grey-darker mb-8">
    Legend:
    <span class="inline-block my-1  mx-1 sm:mx-2 bg-green-lightest text-green-dark rounded-full px-4 py-1">Good use case</span>
    <span class="inline-block my-1  mx-1 sm:mx-2 bg-orange-lightest text-orange-dark rounded-full px-4 py-1">Some drawbacks</span>
    <span class="inline-block my-1  ml-1 sm:ml-2 bg-red-lightest text-red-dark rounded-full px-4 py-1">Strong limitations</span>
</div>

- **Jobs, Cron**

    Jobs, cron tasks and batch processes are very good candidates for FaaS. The scaling model of AWS Lambda can lead to very high throughput in queue processing, and the pay-per-use billing model can sometimes result in drastic costs reduction.
    
    The main limitation at the moment is the lack of documentation on this topic, as well as the lack of native integration with existing queue libraries like Laravel Queues.

- **API**

    APIs run well on AWS Lambda thanks to the API Gateway integration.
    
    Performances are now similar to what you could expect on traditional VPS, with the exception of cold starts that can occasionally add a few hundreds of ms to some requests. While cold starts can be mitigated, those can be a deal breaker with real time APIs where response time is critical.

- **API with MySQL/PostgreSQL**

    MySQL, PostgreSQL or Aurora imply using [AWS RDS](https://aws.amazon.com/rds/), which means using a VPC. Because of that cold starts get much worse: around 5 seconds. While this can be acceptable for some scenarios, for most APIs this is a deal breaker. This is why we will rate it "red" for now as this is a "strong limitation". AWS has planned to remove this caveat in 2019.
    
    Read the [full "Databases" documentation](/docs/environment/database.md) to learn more.

- **Website**

    Websites can run fine on AWS Lambda. Assets can be served via AWS S3. That requires a bit of setup but this is documented in the ["Websites" documentation](/docs/websites.md).
    
    Performances are as good as any server. Cold starts often have less impact in websites than in APIs: a full page load *and render* in a browser is often a few seconds.

- **Website with MySQL/PostgreSQL**

    Just like with APIs, websites using MySQL or PostgreSQL will suffer from longer cold starts due to VPCs.
    
    However such delays can be more acceptable on websites than on APIs, which explains why APIs are rated "red" and websites "orange".

- **Legacy application**

    Migrating a legacy PHP application to Bref and Lambda can be a challenge. First, as explained above, the limitations that come with MySQL/PostgreSQL often apply. On top of that legacy applications tend to be extra slow and large which can make performances suffer.
    
    One could also expect to rewrite a good amount of code to make the application fit for Lambda. For example file uploads and sessions often need to be adapted to work with the read-only filesystem. Cron tasks, scripts or asynchronous jobs must be made compatible with Lambda and possibly SQS. 

    As of August 2019 there is only one such thing in existence (to the extent of our knowledge ):
    [Laravel Vapor](https://vapor.laravel.com/)

    Laravel Vapor is an auto-scaling, serverless deployment platform for Laravel, powered by AWS Lambda. 

    Vapor abstracts the complexity of managing Laravel applications on AWS Lambda, as well as interfacing those applications with SQS queues, databases, Redis clusters, networks, CloudFront CDN, and more. Some highlights of Vapor's features include:

    - Auto-scaling web / queue infrastructure fine tuned for Laravel
    - Zero-downtime deployments and rollbacks
    - Environment variable / secret management
    - Database management, including point-in-time restores and scaling
    - Redis Cache management, including cluster scaling
    - Database and cache tunnels, allowing for easy local inspection
    - Automatic uploading of assets to Cloudfront CDN during deployment
    - Unique, Vapor assigned vanity URLs for each environment, allowing immediate inspection
    - Custom application domains
    - DNS management
    - Certificate management and renewal
    - Application, database, and cache metrics
    - CI friendly
    
    Not impossible, but definitely not the easiest place to start.

## Getting started

Get started with Bref by reading the [installation documentation](installation.md).

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

Bref (which means "brief" in french) comes as an open source Composer package and helps you deploy PHP applications to [AWS](https://aws.amazon.com) and run them on [AWS Lambda](https://aws.amazon.com/lambda/).

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

<table class="w-full text-xs sm:text-sm text-gray-700 mt-8 mb-5 table-fixed">
    <tr class="bg-gray-100">
        <th class="p-4"></th>
        <th class="font-normal p-4 border-b border-gray-400">Simplicity</th>
        <th class="font-normal p-4 border-b border-gray-400">Performances</th>
        <th class="font-normal p-4 border-b border-gray-400">Reliability</th>
    </tr>
    <tr class="border-b border-gray-200">
        <td class="p-4 bg-gray-100 font-bold border-r border-gray-400">
            Jobs, Cron
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-400"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-400"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-400"></span>
        </td>
    </tr>
    <tr class="border-b border-gray-200">
        <td class="p-4 bg-gray-100 font-bold border-r border-gray-400">API</td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-400"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-400"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-400"></span>
        </td>
    </tr>
    <tr class="border-b border-gray-200">
        <td class="p-4 bg-gray-100 font-bold border-r border-gray-400">Website</td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-400"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-400"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-400"></span>
        </td>
    </tr>
    <tr class="border-b border-gray-200">
        <td class="p-4 bg-gray-100 font-bold border-r border-gray-400">Legacy application</td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-red-400"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-green-400"></span>
        </td>
        <td class="p-4 text-center">
            <span class="maturity-icon shadow bg-orange-400"></span>
        </td>
    </tr>
    <tr class="text-xs text-center leading-normal text-gray-600">
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

<div class="text-left text-xs text-gray-700 mb-8">
    Legend:
    <span class="inline-block my-1 mx-1 sm:mx-2 bg-green-100 text-green-600 rounded-full px-4 py-1">Good use case</span>
    <span class="inline-block my-1 mx-1 sm:mx-2 bg-orange-100 text-orange-600 rounded-full px-4 py-1">Some drawbacks</span>
    <span class="inline-block my-1 ml-1 sm:ml-2 bg-red-100 text-red-600 rounded-full px-4 py-1">Strong limitations</span>
</div>

- **Jobs, Cron**

    Jobs, cron tasks and batch processes are very good candidates for FaaS. The scaling model of AWS Lambda can lead to very high throughput in queue processing, and the pay-per-use billing model can sometimes result in drastic costs reduction.

    Using Bref, it is possible to implement cron jobs and queue workers using PHP. Bref also provides integration with popular queue libraries, like Laravel Queues and Symfony Messenger.

- **API**

    APIs run on AWS Lambda without problems. Performances are now similar to what you could expect on traditional VPS.

- **Website**

    Websites can run on AWS Lambda. Assets can be served via AWS S3. That requires a bit of setup but this is documented in the ["Websites" documentation](/docs/websites.md). Performances are as good as any server.

- **Legacy application**

    Migrating a legacy PHP application to Bref and Lambda can be a challenge. One could expect to rewrite some parts of the code to make the application fit for Lambda. For example, file uploads and sessions often need to be adapted to work with the read-only filesystem. Cron tasks, scripts or asynchronous jobs must be made compatible with Lambda and possibly SQS. Finally there are no case studies or online examples to help you along the way.

    Not impossible, but definitely not the easiest place to start. As a first step, you can follow the guidelines of [The Twelve-Factor App](https://12factor.net).

## Getting started

Get started with Bref by reading the [installation documentation](installation.md).

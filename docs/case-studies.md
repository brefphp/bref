---
title: Case studies
current_menu: case-studies
introduction: A collection of case studies of serverless PHP applications built using Bref. Learn about performance, costs and migrations from existing projects.
---

This page collects case studies of serverless PHP applications built with or migrated to Bref.

These help learn for real use cases about costs, performance and migration efforts.

## Websites

- [externals.io](https://mnapoli.fr/serverless-case-study-externals/)

  A case study of the migration of [externals.io](https://externals.io/) to AWS Lambda using Bref. This includes performance and costs details.

- [returntrue.win](https://mnapoli.fr/serverless-case-study-returntrue/)

  A case study of the development of the [returntrue.win](https://returntrue.win/) website using AWS Lambda, including a cost analysis.

## Workers

- 🇫🇷 [Enoptea](https://www.enoptea.fr/serverless-et-php/)

  Enoptea is a french startup that migrated their infrastructure of PHP workers from EC2 to Lambda. They halved their AWS costs and increased their performance while spending less time managing their servers.

- [PrettyCI.com](https://mnapoli.fr/serverless-case-study-prettyci/)

  [PrettyCI](https://prettyci.com/) is a SaaS providing continuous integration for PHP coding standards. Internally, it runs PHP-CS-Fixer or CodeSniffer on AWS Lambda using Bref. This article is a good introduction on how AWS Lambda can be a good solution to run workers and background jobs.

- [MyBuilder](https://mybuilder.com)

  MyBuilder is an online marketplace matching tradespeople with home owners. They used Lambda with Bref to create a highly scalable on-demand microservice to generate PDF reports. The solution involved [creating their own layer](https://tech.mybuilder.com/compiling-wkhtmltopdf-aws-lambda-with-bref-easier-than-you-think/) to include a self-compiled binary file to use alongside Bref's base PHP layer.
  
- [PDF reporting generation](https://devops-life.com/blog/2020/03/06/how-serverless-saved-us-for-$2-with-bref-sh/)

  Feedback to understand the benefits of going serverless with a real use case (PDF generation);
They generated 2,000 PDFs in <2 min for 2$ using Symfony applications shipped inside lambdas using Bref.sh

## Others

- [Serverless](https://serverless.com/learn/case-studies/)

  It isn't exactly about PHP over Serverless, but it gives us a general overview of how hundreds of teams are handling with serverless across a diversity of projects. There are a lot of case studies since from [Us Department of Defense streamlining open source contributions](https://serverless.com/blog/dept-of-defense-doc-bot/) until project which has [reduced back-end costs by 95%](https://serverless.com/blog/abstract-partner-program-announcement/) and much more.

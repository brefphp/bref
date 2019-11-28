---
title: Using a database
current_menu: database
introduction: Configure Bref to use a database in your PHP application on AWS Lambda.
---

AWS offers the [RDS](https://aws.amazon.com/rds/) service to run MySQL and PostgreSQL databases.

Here are the database services offered by RDS:

- MySQL
- PostgreSQL
- [Aurora MySQL](https://aws.amazon.com/rds/aurora/): optimized closed-source fork
- [Aurora PostgreSQL](https://aws.amazon.com/rds/aurora/): optimized closed-source fork
- [Aurora Serverless MySQL](https://aws.amazon.com/rds/aurora/serverless/): scales automatically on-demand

> Aurora Serverless can be configured to scale down to 0 instances when unused (which costs $0), however be careful with this option: the database can take up to 30 seconds to un-pause.

All RDS databases can be setup with Lambda in two ways:

1. the database can be made publicly accessible and protected by a username and password
2. the database can be made inaccessible from internet by putting it in a private network (aka [VPC](https://aws.amazon.com/fr/vpc/))

While the first solution is simpler, the second is more secure. Using a VPC also comes with a few limitations that are detailed below.

This page documents how to create databases using VPC (the secure solution). If you want to skip using a VPC you can read the instructions in the "Accessing the database from your machine" section.

## Limitations

> Running a function inside a VPC used to induce a [cold start](/docs/environment/performances.md#cold-starts) of several seconds. This is no longer the case since October 2019.

### Accessing the internet

A database inside a [VPC](https://aws.amazon.com/fr/vpc/) is isolated from the internet. Since a lambda function must run in the VPC to access the database, it cannot access the internet (for example external APIs) or most other AWS services.

To enable internet access for a lambda you will need to create a NAT Gateway in the VPC: you can follow [this tutorial](https://medium.com/@philippholly/aws-lambda-enable-outgoing-internet-access-within-vpc-8dd250e11e12).

Watch out, a NAT Gateway will increase costs (starts at $27 per month). Note that you can use one VPC and one NAT Gateway for multiple projects.

When possible, an alternative to NAT Gateways is to split the work done by a lambda in 2 lambdas: one in the VPC that accesses the database and one outside that accesses the external API. It is also possible to access specific AWS services in a VPC by creating "private endpoints": this is possible for S3, API Gateway, [and more](https://docs.aws.amazon.com/en_pv/vpc/latest/userguide/vpc-endpoints-access.html).

## Creating a database

On the [RDS console](https://console.aws.amazon.com/rds/home):

- click "Create database"
- select the type of database you want to create and fill in the form
- for a simpler configuration leave the default VPC in the last step

Tips to better control costs:

- for non-critical databases you can disable replication
- switch storage to "General Purpose (SSD)" for lower costs
- you can disable "enhanced monitoring" to avoid the associated costs

## Accessing the database from PHP

To retrieve the information needed to let AWS Lambda access the database go into [the RDS dashboard](https://console.aws.amazon.com/rds/home#databases:) and open the database you created.

> It may take some minutes for the database to be created.

Find:

- the "endpoint", which is the hostname of the database (this information is available only after the database creation has completed)

    ℹ️ Instead of connecting via a socket, via `localhost` or an IP address, PHP will connect to MySQL via this hostname.
- the "security group ID" (in the "VPC security groups" section), which looks like `sg-03f68e1100481622b`

    ℹ️ A [security group](https://docs.aws.amazon.com/vpc/latest/userguide/VPC_SecurityGroups.html) is a firewall that restricts access to/from the VPC using "Inbound rules" and "Outbound rules".
- the list of "subnets", which look like `subnet-12f4130e` (there are several subnets)

    ℹ️ An AWS region is divided in [availability zones](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/using-regions-availability-zones.html) (different data centers): there is usually one subnet per availability zone.

Put these information in `serverless.yml` in your function configuration ([read more about this in the Serverless documentation](https://serverless.com/framework/docs/providers/aws/guide/functions/#vpc-configuration)):

```yaml
functions:
    hello:
        ...
        vpc:
            securityGroupIds:
                - sg-03f68e1100481622b
            subnetIds:
                - subnet-12f4130e
                - subnet-c5fe33e5
                - subnet-11aa85dc
                - subnet-85dcf240
```

Now we need to authorize connections to the RDS security group (because the lambda is in the VPC but outside of this VPC group) (https://www.reddit.com/r/aws/comments/8nr8ek/lambda_rds_connection_timeout/):

- open the database configuration in RDS and click the security group
- in the "Inbound" tab click "Edit"
- add a rule: select MySQL/Aurora (or PostgreSQL) and set a "custom" source: select the security group itself (type `sg-` and use the autocompletion)
- save

Your PHP application will be able to connect to the database through the "endpoint" you noted above.

For example a PDO connection string could be:

```
mysql://user:password@dbname.e2sctvp0nqos.us-east-1.rds.amazonaws.com/dbname
```

To learn how to properly store this connection string in your configuration head over to the ["Secrets" section of the Variables documentation](/docs/environment/variables.md#secrets).

Also refer to the [Extensions](/docs/environment/php.md#extensions) section to see if you need to enable any database-specific extensions.

### Learn more

You can learn more about limitations and guidelines from the AWS documentation about [Configuring a lambda to access resources in a VPC](https://docs.aws.amazon.com/lambda/latest/dg/vpc.html).

## Accessing the database from your machine

Since the database is in a VPC, it cannot be accessed from the outside (i.e. the internet). You cannot connect to your database with MySQL Workbench or other administration tools.

When creating a new project, the database can be set up through several means:

- via a lambda that loads a SQL dump into the database (for example a [console lambda](/docs/runtimes/console.md))
- by temporarily exposing the database on the internet

Exposing your database to the internet is risky and should only be done for a few minutes or hours (for example to load a SQL dump).

To do so, open your RDS instance in the [RDS console](https://console.aws.amazon.com/rds/home#databases:):

- click "Modify"
- enable "Public accessibility"
- click "Continue"
- select "Apply immediately" (**do not skip this step**)
- click "Modify DB Instance"

Now click the *security group* of the instance:

- open the "Inbound" tab
- click "Edit"
- add a rule: select "MySQL/Aurora" (or PostgreSQL) and set "Anywhere" as the source (you could also set your public IP for increased security)
- save

Connect to your database using your favorite tool. For example using `mysql` in the CLI:

```bash
mysql -h<endpoint> -u<root user> -p <database>
```

**Remember to revert those changes once you are done!**

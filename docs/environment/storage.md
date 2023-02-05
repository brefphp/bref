---
title: Storage
current_menu: storage
introduction: Learn how to store data and files in serverless PHP applications running on AWS Lambda.
---

Here is a simplified overview of the filesystem on AWS Lambda:

```bash
/opt/
    # Where Lambda runtimes and layers (like Bref) are unzipped
/var/task/
    # Where your application code is unzipped
/tmp/
    # Temporary files
...
```

The `/var/task` directory is [limited to 250MB](https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-limits.html#function-configuration-deployment-and-execution). If you hit that limit, you can deploy [via Docker images instead](../web-apps/docker.md).

The filesystem on AWS Lambda **is read-only**, except for the `/tmp` directory.

On top of that, the filesystem is not shared between instances of a lambda when it scales up. For example a file `/tmp/foo.json` will not be shared across instances of the same lambda. Since a lambda can scale up or down at any time, data in the `/tmp` directory can be lost.

## Application data

Application data **must not** be stored in `/tmp` because of the behavior described above.

Instead, data can be stored in [databases](/docs/environment/database.md) or in storage services like AWS S3.

### S3 storage

It is possible to deploy a S3 bucket in `serverless.yml` using the <a href="https://github.com/getlift/lift/blob/master/docs/storage.md">`Storage` feature of the Lift plugin</a>. For example:

```yaml
# serverless.yml

# ...

provider:
    environment:
        BUCKET_NAME: ${construct:reports-bucket.bucketName}

constructs:
    reports-bucket:
        type: storage
```

Read more <a href="https://github.com/getlift/lift/blob/master/docs/storage.md">in the Lift documentation</a>.

## Application cache

Performance-wise, using AWS S3 for storing the application cache is not ideal.

The following solutions can be used instead:

- pre-generate the cache in the project directory before deploying
- store the cache into the `/tmp` directory
- store the cache into a distributed cache service like Memcache, Redis or DynamoDB (an AWS service which fits the pay-per-request model nicely)

### Pre-generating the cache

Some frameworks allow to pre-generate some caches. For example in Symfony the container can be compiled via `bin/console cache:warmup`, or in Laravel the config cache can be generated before deploying.

When possible **this is the best solution**: no generation will occur in production, and reading from the filesystem will be fast.

### Store in the `/tmp` directory

Some framework or library caches must be written into files. In that case storing in the `/tmp` directory is a good solution.

Remember that anything stored in `/tmp` will be lost when a lambda stops. When a lambda starts, the `/tmp` directory will be empty so the cache will be generated again.

Note that this is useful for deployments: no need to clear caches on deployments since a new version of the lambda will run on new instances (with an empty `/tmp` directory).

This solution is ideal when the cached data is fast to generate and never changes (e.g. template caching, framework caches).

### Store in a distributed cache service

Using a distributed cache service has the following advantages:

- the cache is not lost when the lambda scales down
- the cache is not lost when deploying
- the cache is shared between all lambda instances

The disadvantage is that if the data format changes between deployments, then a deployment strategy must be used to either clear the cache and regenerate it, or separate the cache between application versions.

Cache services that can be used include for example Redis, Memcache or DynamoDB. AWS offers those as managed services through AWS ElastiCache (Redis, Memcache) or DynamoDB.

Note that Redis and Memcache (through ElastiCache) run even when not used, which incurs costs. DynamoDB is a little slower than both of those but can be deployed in a "pay-per-request" mode where costs are proportional to the usage. There is a package implementing the PSR cache interfaces using DynamoDB ([rikudou/psr6-dynamo-db](https://github.com/RikudouSage/DynamoDbCachePsr6)).

This solution is ideal for cache data that can change during the life of the application (e.g. caching a website menu, an API response…).

#### Deploying DynamoDB tables

As any service, DynamoDB tables can be deployed via CloudFormation using the `resources` key in `serverless.yml`:

```yaml
service: app
...

resources:
    Resources:
        CacheTable:
            Type: AWS::DynamoDB::Table
            Properties:
                AttributeDefinitions: # only keys are defined here, other attributes are dynamic
                    -   AttributeName: id # adds a mandatory id field
                        AttributeType: S # the type of id is a string
                BillingMode: PAY_PER_REQUEST # billed for each request instead of paying for a constant capacity
                TimeToLiveSpecification: # deletes cache keys automatically based on a ttl field which contains a timestamp
                    AttributeName: ttl
                    Enabled: true
                KeySchema:
                    -   AttributeName: id
                        KeyType: HASH # the type of key, HASH means partition key (similar to primary keys in SQL)
```

We need to allow code in Lambda functions to access DynamoDB.
We can also pass the table name as an environment variable to the application.

```yaml
service: app
provider:
    iam:
        role:
            statements:
                - Effect: Allow
                    Resource: !GetAtt CacheTable.Arn
                    Action:
                        - dynamodb:DescribeTable
                        - dynamodb:Query
                        - dynamodb:Scan
                        - dynamodb:GetItem
                        - dynamodb:PutItem
                        - dynamodb:UpdateItem
                        - dynamodb:DeleteItem
    environment:
        # This environment variable will contain the table name
        DYNAMODB_CACHE_TABLE: !Ref CacheTable
```

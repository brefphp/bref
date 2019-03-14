---
title: Dealing with static files
currentMenu: php
introduction: Deploy and host static websites or assets alongside the PHP application running on AWS Lambda.
---

## Creating a S3 bucket

Create a bucket with the same name as the domain name. For example `assets.example.com`.

Create a S3 bucket:

```bash
aws s3 mb s3://<bucket-name> --region=<region>
```

Enable website hosting on the bucket:

```bash
aws s3 website s3://<bucket-name> --index-document index.html
```

The website is now published at `<bucket-name>.s3-website.<region>.amazonaws.com`.

You can now [setup your custom domain to point to this URL](custom-domains.md#custom-domains-for-static-websites-on-s3).

## Uploading code to S3

Use the `sync` command to upload files into the bucket:

```bash
aws s3 sync <directory> s3://<bucket-name> --delete --acl public-read
```

Be aware that `--acl public-read` makes the content of the bucket public!

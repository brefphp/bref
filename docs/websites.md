---
title: Creating serverless PHP websites
currentMenu: php
introduction: Learn how to deal with assets and static files to deploy serverless PHP websites.
---

Websites usually contain 2 parts:

- PHP code, which runs on AWS Lambda and is [served by API Gateway](/docs/runtimes/http.md)
- static files, which can be hosted and [served by AWS S3](https://docs.aws.amazon.com/AmazonS3/latest/dev/WebsiteHosting.html)

It is possible to use these services with custom domains to build websites, however we quickly face the following limitations:

- API Gateway is *HTTPS only*
- S3 is *HTTP only*

A simple way to support both HTTP and HTTPS is to use a free CDN like [Cloudflare.com](https://www.cloudflare.com/).

If you would rather use AWS, the guide below explains how to use [CloudFront](https://aws.amazon.com/cloudfront/) (the AWS CDN) to serve both API Gateway and S3 under the same domain with HTTP and HTTPS.

## Hosting static files with S3

### Creating a S3 bucket

Create a bucket with the same name as the domain name. For example `assets.example.com`.

Create a S3 bucket:

```bash
aws s3 mb s3://<bucket-name> --region=<region>
```

Enable website hosting on the bucket:

```bash
aws s3 website s3://<bucket-name> --index-document index.html
```

The website is now published at `<bucket-name>.s3-website-<region>.amazonaws.com`.

You can now [setup your custom domain to point to this URL](custom-domains.md#custom-domains-for-static-websites-on-s3).

### Uploading code to S3

Use the `sync` command to upload files into the bucket:

```bash
aws s3 sync <directory> s3://<bucket-name> --delete --acl public-read
```

Be aware that `--acl public-read` makes the content of the bucket public!

## Serving PHP and static files via CloudFront

This section assumes you have already deployed your static files on S3 as shown above.

As explained at the beginning of this page, CloudFront will let us:

- serve the PHP application and static files under the same domain
- support both HTTP and HTTPS

This diagram helps understand how CloudFront works:

![](cloudfront.png)

The `template.yaml` example below:

- receives HTTP and HTTPS requests
- serves everything URL that starts with `/assets/` using S3 (static files)
- sends all the other requests to PHP via API Gateway

Feel free to customize the `/asset/` path. If your application is a JavaScript application backed by a PHP API, you will want to invert API Gateway and S3 (set S3 as the `DefaultCacheBehavior` and serve API Gateway under a `/api/` path).

```yaml
Resources:
    ...
    WebsiteCDN:
        Type: AWS::CloudFront::Distribution
        Properties:
            DistributionConfig:
                Enabled: true
                # Cheapest option by default (https://docs.aws.amazon.com/cloudfront/latest/APIReference/API_DistributionConfig.html)
                PriceClass: PriceClass_100
                # Origins are where CloudFront fetches content
                Origins:
                    # The website (AWS Lambda)
                    -   Id: Website
                        DomainName: !Sub '${ServerlessRestApi}.execute-api.${AWS::Region}.amazonaws.com'
                        OriginPath: '/Prod'
                        CustomOriginConfig:
                            OriginProtocolPolicy: 'https-only' # API Gateway only supports HTTPS
                    # The assets (S3)
                    -   Id: Assets
                        # Watch out, use s3-website URL (https://stackoverflow.com/questions/15309113/amazon-cloudfront-doesnt-respect-my-s3-website-buckets-index-html-rules#15528757)
                        DomainName: <bucket-name>.s3-website-<region>.amazonaws.com
                        CustomOriginConfig:
                            OriginProtocolPolicy: 'http-only' # S3 websites only support HTTP
                # The default behavior is to send everything to AWS Lambda
                DefaultCacheBehavior:
                    AllowedMethods: [GET, HEAD, OPTIONS, PUT, POST, PATCH, DELETE]
                    TargetOriginId: Website # the PHP application
                    # Disable caching for the PHP application
                    DefaultTTL: 0
                    MinTTL: 0
                    MaxTTL: 0
                    # https://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/aws-properties-cloudfront-distribution-forwardedvalues.html
                    ForwardedValues:
                        QueryString: true
                        # We must *not* forward the `Host` header else it messes up API Gateway
                        Headers:
                            - 'Accept'
                            - 'Accept-Language'
                            - 'Origin'
                            - 'Referer'
                    ViewerProtocolPolicy: redirect-to-https
                CacheBehaviors:
                    # Assets will be served under the `/assets/` prefix
                    -   PathPattern: 'assets/*'
                        TargetOriginId: Assets # the static files on S3
                        AllowedMethods: [GET, HEAD]
                        ForwardedValues:
                            # No need for all that with assets
                            QueryString: 'false'
                            Cookies:
                                Forward: none
                        ViewerProtocolPolicy: redirect-to-https
                        Compress: true # Serve files with gzip for browsers that support it (https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/ServingCompressedFiles.html)
                ViewerCertificate:
                    CloudFrontDefaultCertificate: true
```

> The first deployment takes a lot of time (20 minutes) because CloudFront is a distributed service. The next deployments that do not modify CloudFront's configuration will not suffer from this delay.

### Setting up a domain name

Just like in the "[Custom domains](/docs/environment/custom-domains.md)" guide, you need to register your domain in **ACM** (AWS Certificate Manager) to get a HTTPS certificate.

- open [this link](https://console.aws.amazon.com/acm/home?region=us-east-1#/wizard/) or manually go in the ACM Console and click "Request a new certificate" **in the `us-east-1` region** (CloudFront requires certificates from `us-east-1`)
- add your domain name and click "Next"
- choose the domain validation of your choice
    - domain validation will require you to add entries to your DNS configuration
    - email validation will require you to click a link you will receive in an email sent to `admin@your-domain.com`

After validating the domain and the certificate we can now configure it in `template.yaml`.

Copy the ARN of the ACM certificate. It should look like this:

```
arn:aws:acm:us-east-1:216536346254:certificate/322f12ee-1165-4bfa-a41f-08c932a2935d
```

Add your domain name under `Aliases` and configure `ViewerCertificate` to use your custom HTTPS certificate:

```yaml
Resources:
    ...
    WebsiteCDN:
        Type: AWS::CloudFront::Distribution
        Properties:
            DistributionConfig:
                ...
                # Custom domain name
                Aliases:
                    - <custom-domain> # e.g. example.com
                ViewerCertificate:
                    # ARN of the certificate created in ACM
                    AcmCertificateArn: <certificate-arn>
                    # See https://docs.aws.amazon.com/fr_fr/cloudfront/latest/APIReference/API_ViewerCertificate.html
                    SslSupportMethod: 'sni-only'
```

The last step will be to point your domain name to the CloudFront URL:

- open [the CloudFront dashboard](https://console.aws.amazon.com/cloudfront/home?region=eu-west-1#) and look for the URL under `Domain Name`
- create a CNAME to point your domain name to this URL
    - if you use Route53 you can read [the official guide](https://docs.aws.amazon.com/Route53/latest/DeveloperGuide/routing-to-cloudfront-distribution.html)
    - if you use another registrar and you want to point your root domain (without `www.`) to CloudFront, you will need to use a registrar that supports this (for example [CloudFlare allows this with a technique called CNAME flattening](https://support.cloudflare.com/hc/en-us/articles/200169056-Understand-and-configure-CNAME-Flattening))

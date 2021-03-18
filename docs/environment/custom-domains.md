---
title: Custom domain names
current_menu: custom-domains
introduction: Configure custom domain names for your web applications.
---

API Gateway generates random domain names for our applications:

```
https://<random>.execute-api.<region>.amazonaws.com/
```

It is possible to replace those URLs by a custom domain.

> These guides assume you already own the domain name you will want to use.

## Custom domains for HTTP lambdas

The first thing to do is register the domain in **ACM** (AWS Certificate Manager) to get a HTTPS certificate. This step is not optional.

- open [this link](https://console.aws.amazon.com/acm/home?region=us-east-1#/wizard/) or manually go in the ACM Console and click "Request a new certificate" in the `us-east-1` region (the region used for global "edge" certificates)
- add your domain name and click "Next"
- choose the domain validation of your choice
    - domain validation will require you to add CNAME entries to your DNS configuration
    - email validation will require you to click a link you will receive in an email sent to `admin@your-domain.com`

After validating the domain and the certificate we can now link the custom domain to our application via API Gateway.

- open [API Gateway's "Custom Domain" configuration](https://console.aws.amazon.com/apigateway/main/publish/domain-names)
- **switch to the region of your application**
- click "Create"
- enter your domain name, select the certificate you created above and save
- edit the domain that was created
- click "Configure API mappings" to add an "API mapping": select your application and the `$default` stage (or `dev` in some cases), for example:

  ![](custom-domains-path-mapping.png)
- after saving the "API mappings", find the `API Gateway domain name` in the "Configurations" tab
- create a CNAME entry in your DNS to point your domain name to this domain

After waiting for the DNS change to propagate (sometimes up to several hours) your website is now accessible via your custom domain.

> You can also take a look at the plugin [serverless-domain-manager](https://www.serverless.com/plugins/serverless-domain-manager).  
> It handles the custom domain creation and optionally adds the Route53 record if asked. It is still necessary to create the ACM certificate manually.
> 
> A basic implementation is proposed here : https://www.serverless.com/blog/serverless-api-gateway-domain#create-a-custom-domain-in-api-gateway  


## Custom domains for static files on S3

Some applications serve static files hosted on AWS S3. You can read the [Websites](/docs/websites.md#hosting-static-files-with-s3) documentation to learn more.

The S3 bucket can be accessed at this URL: `https://<bucket>.s3.amazonaws.com/` (supports both HTTP and HTTPS).

To use a custom domain for a S3 static website the process lies in 2 steps:

- name the S3 bucket like the wanted domain name

  For example for the http://www.example.com website, the S3 bucket has to be named `www.example.com`
- point the domain to the S3 URL via DNS

  In our example the DNS entry to create would be a CNAME for `www.example.com` pointing to `www.example.com.s3.amazonaws.com`

### Static websites

If you are hosting a full static website with HTML files ([per this documentation](https://docs.aws.amazon.com/AmazonS3/latest/dev/WebsiteHosting.html)), the URLs to use will be different:

```bash
http://<bucket>.s3-website-<region>.amazonaws.com/
# or
http://<bucket>.s3-website.<region>.amazonaws.com/
```

In that case you need to use the domains above for the CNAME.

Note that the URL is **HTTP-only** and [depends on the region](https://docs.aws.amazon.com/general/latest/gr/rande.html#s3_website_region_endpoints).

To add HTTPS to your website for free it is possible to use a CDN like [CloudFlare](https://cloudflare.com/) (simplest) or [AWS CloudFront](/docs/websites.md#serving-php-and-static-files-via-cloudfront).

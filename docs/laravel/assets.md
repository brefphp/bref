---
introduction: Deploy assets for serverless Laravel applications on AWS Lambda using Bref.
---

# Assets

To deploy Laravel websites, assets need to be served from AWS S3. The easiest approach is to use the [Server-side website construct of the Lift plugin](https://github.com/getlift/lift/blob/master/docs/server-side-website.md).

This will deploy a CloudFront distribution (CDN) that will act as a proxy: it will serve static files directly from S3 and will forward everything else to Lambda. This is very close to how traditional web servers like Apache or Nginx work, which means your application doesn't need to change! For more details, read [the official documentation](https://github.com/getlift/lift/blob/master/docs/server-side-website.md#how-it-works).

First install the plugin:

```bash
serverless plugin install -n serverless-lift
```

Then add this configuration to your `serverless.yml` file:

```yml filename="serverless.yml" {10,12-20}
service: laravel
provider:
    # ...

functions:
    # ...

plugins:
    - ./vendor/bref/bref
    - serverless-lift

constructs:
    website:
        type: server-side-website
        assets:
            '/js/*': public/js
            '/css/*': public/css
            '/favicon.ico': public/favicon.ico
            '/robots.txt': public/robots.txt
            # add here any file or directory that needs to be served from S3
```

Before deploying, compile your assets:

```bash
npm run prod
```

Now deploy your website using `serverless deploy`. Lift will create all required resources and take care of uploading your assets to S3 automatically.

TODO
For more details, see the [Websites section](../web-apps/website-assets.md) of this documentation and the official [Lift documentation](https://github.com/getlift/lift/blob/master/docs/server-side-website.md).

### Assets in templates

Assets referenced in templates should be via the `asset()` helper:

```html
<script src="{{ asset('js/app.js') }}"></script>
```

If your templates reference some assets via direct path, you should edit them to use the `asset()` helper:

```html
- <img src="/images/logo.png">
+ <img src="{{ asset('images/logo.png') }}">
```

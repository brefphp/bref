# What is serverless?

These days the term **serverless** is used to describe a lot of different variations on the same general theme; that you
can run your code reliably and securely without needing to concern yourself with maintaining the server that executes it.

There are, of course, still servers involved, but somebody else has the job of keeping them running and worrying about 
updates, patches, hardware maintenance, etc. This allows you to focus on coding to meet your business requirements rather 
than spending effort keeping servers running.

Vendors like [AWS](https://aws.amazon.com) are able to offer us focused services much cheaper and simpler than we would be able to achieve by 
running them ourselves. Need a message queue? You can just use the queue service. Want to store files? There's a dedicated 
service for that. The beauty is that we are only billed for what we actually use and nothing else.

Taking this concept to the extreme, we have arrived at a billing model known as **FaaS** (Functions as a Service). In this
case we are given space on a server to upload our business code to, and while AWS takes care of setting up everything,
and keeping the code available 24/7, we are only charged _while the code is actually being executed_.

## What is AWS Lambda?

Quite simply, **Lambda** is AWS' name for their FaaS service.

## What can I do with FaaS / Lambda?

As a simple example, we could create a static HTML website and upload it to AWS Lambda. Once a visitor arrives, Lambda 
returns a response to the user with your webpage, and you are charged a fraction of a cent by Amazon for the time it took
to process the request and return the response.

If we want to do anything dynamic, we can use our favourite programming languages to do things like connecting to a database
to fetch content, process `POST` requests with forms, etc. Again, AWS takes care of the execution for us, and only bills
us for the resources it used between accepting the request and returning the response.

Essentially, we have a webserver which only costs us money while visitors are actually using the site!

## How does Lambda actually help me?

### A common problem

Let's think about a possible evolution path of a typical web application. You might start off by renting a 
[single EC2 web server](https://aws.amazon.com/ec2/), costing you $17.25 a month.

Last year you bought an SSL certificate from some online vendor, which cost you about $250. This year you heard that 
[AWS offers **free** SSL](https://aws.amazon.com/certificate-manager/), but to use it with your EC2 server you _need_ to
attach it to a [load balancer](https://aws.amazon.com/ec2/). The load balancer costs you $16.88 per month, which is 
just over $200 per year. Suddenly the _free_ SSL is costing you almost as much as buying a certificate outright!

As your website becomes more popular, you find that your single web server instance is having trouble keeping up with demand,
so you add more and more web servers behind your load balancer. For the purpose of this example, let's say we find that 
three web servers will be sufficient to handle the traffic we need to serve.

You have a choice to keep them running all the time to cope with spikes in traffic (each costing a further $17.25 per month),
 or in order to keep costs low you can spend time configuring auto-scaling rules. While this will take care of starting 
 up and shutting down servers as demand increases or drops, the boot time can take several minutes and your visitors 
 will be seeing unwanted results in that time.

Now that you have multiple web servers you need to have a centralised data store. So you decide to 
[rent space in a database](https://aws.amazon.com/rds/), which costs around the same as one of your web servers ($17.25).

 
 In total your monthly spend could be:
 
 <table class="table table-striped">
     <thead>
         <tr>
             <th>Service</th>
             <th style="text-align: right">Cost per instance</th>
             <th style="text-align: right">Num instances</th>
             <th style="text-align: right">Total</th> 
         </tr>
     </thead>
     <tbody>
        <tr>
            <td>EC2 Web Server</td>
            <td style="text-align: right">$17.25</td>
            <td style="text-align: right">3</td>
            <td style="text-align: right">$51.75</td>
        </tr>
        <tr>
            <td>ELB Load Balancer</td>
            <td style="text-align: right">16.88</td>
            <td style="text-align: right">1</td>
            <td style="text-align: right">$16.88</td>
        </tr>
        <tr>
            <td>RDS Database</td>
            <td style="text-align: right">$17.25</td>
            <td style="text-align: right">1</td>
            <td style="text-align: right">$17.25</td>
        </tr>
        <tr>
            <td>ACM SSL Cert</td>
            <td style="text-align: right">$0</td>
            <td style="text-align: right">1</td>
            <td style="text-align: right">$0</td>
        </tr>
        <tr>
            <td style="text-align: right" colspan="4">$85.88</td>
        </tr>  
     </tbody>
 </table>
 
 ### Lambda / FaaS to the rescue

Despite sounding scary at first, FaaS/Lambda immediately solves a bunch of problems for us.

- We no longer need to have our own web server running all the time
- Lambda will auto-scale to meet demand, so we don't need multiple servers or scaling rules
- You can attach your SSL certificate directly to your Lambda code, so we don't need that load balancer any more
- You're not billed at all for the first million requests each month

Assuming our website receives 20 million visits per month, our costs on Lambda would be:

<table class="table table-striped">
     <thead>
         <tr>
             <th>Service</th>
             <th style="text-align: right">Cost per instance / request</th>
             <th style="text-align: right">Num instances / requests</th>
             <th style="text-align: right">Total</th> 
         </tr>
     </thead>
     <tbody>
        <tr>
            <td>Lambda</td>
            <td style="text-align: right">$0.0000002</td>
            <td style="text-align: right">19,000,000</td>
            <td style="text-align: right">$3.80</td>
        </tr>
        <tr>
            <td>RDS Database</td>
            <td style="text-align: right">$17.25</td>
            <td style="text-align: right">1</td>
            <td style="text-align: right">$17.25</td>
        </tr>
        <tr>
            <td>ACM SSL Cert</td>
            <td style="text-align: right">$0</td>
            <td style="text-align: right">1</td>
            <td style="text-align: right">$0</td>
        </tr>
        <tr>
            <td style="text-align: right" colspan="4">$21.05</td>
        </tr>  
     </tbody>
 </table>
 
 You can see that with a little bit of work and a slight alteration in how we think about hosting our web applications,
 we can serve a highly-scalable website to our visitors, and reduce our costs by three quarters. In fact, it's only 
 the database which is really costing us any money now, and that [might not be a problem for too much longer](https://aws.amazon.com/rds/aurora/serverless/).
  

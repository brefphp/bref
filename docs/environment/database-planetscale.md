---
title: Using PlanetScale with Bref on AWS Lambda
current_menu: database-planetscale
introduction: Configure Bref to use a PlanetScale database in your PHP application on AWS Lambda.
---

[PlanetScale](https://planetscale.com/) is a hosted serverless MySQL database based on the Vitess engine ([learn more](https://planetscale.com/docs/concepts/what-is-planetscale)).

Amongst other features, it offers the following benefits compared to running a database on AWS:

- Simple to set up: no VPC (virtual private network) to set up, no instances to configure.
- Runs [the Vitess MySQL engine](https://planetscale.com/blog/vitess-for-the-rest-of-us), which offers better scalability and supports a lot more concurrent connections [via built-in connection pooling](https://planetscale.com/blog/one-million-connections).
- Offers a [free database in the Hobby plan](https://planetscale.com/pricing), and paid plans are usage-based.
- Since it does not require a VPC, we do not need to set up and pay [for a NAT Gateway](database.md#accessing-the-internet).

One extra feature worth mentioning is [the branching concept](https://planetscale.com/docs/concepts/branching): it enables testing schema changes before deploying them in production without downtime.

## Getting started

To use PlaneScale with Bref, start [by creating a PlanetScale account](https://planetscale.com/).

Then, create a database in the same region as your Bref application.

![](./database/planetscale-create.png)

> The database is created with an initial development branch: `main`. PlanetScale [has a branching concept](https://planetscale.com/docs/concepts/branching) that lets you test schema changes in a development branch, then promote it to production, or even create new branches (isolated copies of the production schema) off of production to use for development.

You can now click the **Connect** button and select "Connect with: PHP (PDO)". That will let you retrieve the host, database name, user and password.

Here is a simple example that connects to the database using PDO and performs a few queries:

```php
<?php
$host = '<host>';
$dbname = '<database name>';
$user = '<user>';
$password = '<password>';

$pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password, [
    PDO::MYSQL_ATTR_SSL_CA => openssl_get_cert_locations()['default_cert_file'],
]);

$pdo->exec('CREATE TABLE IF NOT EXISTS test (id INT NOT NULL AUTO_INCREMENT, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
$pdo->exec('INSERT INTO test (name) VALUES ("test")');
var_dump($pdo->query('SELECT * FROM test')->fetchAll());
```

Note the `PDO::MYSQL_ATTR_SSL_CA` flag: while we connect via a username and password, [the connection happens over SSL](https://planetscale.com/docs/concepts/secure-connections) to secure against man-in-the-middle attacks. To avoid hardcoding the location of the file containing SSL certificates, we retrieve its path via `openssl_get_cert_locations()['default_cert_file']`.

In Bref, the file is located here: `/opt/bref/ssl/cert.pem`.

## Laravel

_This guide assumes you have already set up a Laravel application by following [the Bref documentation for Laravel](../frameworks/laravel.md)._

To configure Laravel to use the PlanetScale database, we need to set it up via environment variables.

If you deploy a `.env` file, set up the following variables:

```bash
DB_CONNECTION=mysql
DB_HOST=<host url>
DB_PORT=3306
DB_DATABASE=<database name>
DB_USERNAME=<user>
DB_PASSWORD=<password>
# Connect via SSL (https://planetscale.com/docs/concepts/secure-connections)
MYSQL_ATTR_SSL_CA=/opt/bref/ssl/cert.pem
```

If you don't deploy the `.env` file, you can configure the variables in `serverless.yml`:

```yaml
provider:
    # ...
    environment:
        DB_HOST: <host url>
        DB_DATABASE: <database name>
        DB_USERNAME: <user>
        DB_PASSWORD: ${ssm:/my-app/database-password}
        # Connect via SSL (https://planetscale.com/docs/concepts/secure-connections)
        MYSQL_ATTR_SSL_CA: /opt/bref/ssl/cert.pem
```

Note that the `DB_PASSWORD` value is sensitive and can be set up as a secret via SSM. Read about [Secret variables](./variables.md#secrets) to learn more.

Don't forget to deploy the changes:

```bash
serverless deploy
```

Now that Laravel is configured, we can run `php artisan migrate` in AWS Lambda to set up our tables:

```bash
serverless bref:cli --args="migrate"
```

That's it! Our database is ready to use.

## Symfony

_This guide assumes you have already set up a Symfony application by following [the Bref documentation for Symfony](../frameworks/symfony.md)._

First, make sure you have installed Doctrine, or [follow these docs to do so](https://symfony.com/doc/current/doctrine.html#installing-doctrine).

To configure Symfony to use the PlanetScale database, we need to set it up via environment variables.

If you deploy a `.env` file, set up the following variables:

```bash
DATABASE_URL="mysql://<USERNAME>:<PASSWORD>@<HOST_URL>:3306/<DATABASE_NAME>?serverVersion=8.0"
```

If you don't deploy the `.env` file, you can configure the variables in `serverless.yml`:

```yaml
provider:
    # ...
    environment:
        DATABASE_URL: ${ssm:/my-app/database-url}
```

Note that the `DATABASE_URL` value is sensitive and can be set up as a secret via SSM. Read about [Secret variables](./variables.md#secrets) to learn more.

Finally, edit the `config/packages/doctrine.yaml` configuration file to set up [the SSL connections](https://planetscale.com/docs/concepts/secure-connections):

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        options:
            # Connect to the database via SSL
            !php/const:PDO::MYSQL_ATTR_SSL_CA: /opt/bref/ssl/cert.pem

# ...
```

Let's deploy the changes:

```bash
serverless deploy
```

Now that Symfony is configured, we can run the `bin/console doctrine:migrations:migrate` command in AWS Lambda to set up our tables:

```bash
serverless bref:cli --args="doctrine:migrations:migrate"
```

That's it! Our database is ready to use.

## Digging deeper

### MySQL compatibility

### Database import

### Schema changes workflow

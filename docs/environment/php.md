---
title: Configuring PHP
currentMenu: php
introduction: Configure PHP versions, extensions and options for your serverless application using Bref.
---

## php.ini

Bref's default `php.ini` is `/opt/bref/etc/php/php.ini` in the bref layer.

### Overriding Bref Defaults or Adding other configuration settings.
You can create configuration files to customize PHP configuration:

1. create a `php/config.d/` subdirectory in your project.
1. create a `php.ini` file inside that directory _(the name of the file does not matter, but it must have an `.ini` extensions)_

PHP will automagically scan that directory and load the `*.ini` files you place there, overridding any of Bref's default settings.

### PHP_INI_SCAN_DIR Environment Variable.
If you would like to have PHP scan a different directory in your project, simply set the environment varialbe to and absolute path to the directory you want scanned.

> The `PHP_INI_SCAN_DIR` must contain an absolute path. Since your code is placed in `/var/task` on AWS Lambda the environment variable should contain something like `/var/task/my/different/dir`.

Here is an example of how to define the Environment variable in your SAM template:

```yaml
Resources:
    MyFunction:
        Type: AWS::Serverless::Function
        Properties:
            # ...
            Environment:
                Variables:
                    PHP_INI_SCAN_DIR: '/var/task/php'
```

## Extensions

Bref strives to include the most common set of extensions in the PHP layer. If a major PHP extension is missing please open an issue to discuss it. Due to space limitations in AWS Lambda tasks, we can not reasonably add every possible extension to the Bref layer. We do provide instructions for how you may bring your own extensions.

The layer bundles two categories of extensions.

1. Those that are enabled and can not be disabled.
2. Those that are disabled and can be enabled.

## Bundled Extensions
### Enabled, can not be disabled.
<table>
  <tbody>
    <tr>
      <td  align="left" valign="top">
        <ul>
        <li>Core</li>
        <li><a href="http://php.net/manual/en/intro.ctype.php">ctype</a></li>
        <li><a href="http://php.net/manual/en/book.curl.php">curl</a></li>
        <li>date</li>
        <li><a href="http://php.net/manual/en/book.dom.php">dom</a></li>
        <li><a href="http://php.net/manual/en/book.exif.php">exif</a></li>
        <li><a href="http://php.net/manual/en/book.fileinfo.php">fileinfo</a></li>
        <li><a href="http://php.net/manual/en/book.filter.php">filter</a></li>
        <li><a href="http://php.net/manual/en/book.ftp.php">ftp</a></li>
        <li><a href="http://php.net/manual/en/book.gettext.php">gettext</a></li>
        <li><a href="http://php.net/manual/en/book.hash.php">hash</a></li>
        <li><a href="http://php.net/manual/en/book.iconv.php">iconv</a></li>
        </ul>
      </td>
      <td  align="left" valign="top">
        <ul>
        <li><a href="http://php.net/manual/en/book.json.php">json</a></li>
        <li><a href="http://php.net/manual/en/book.libxml.php">libxml</a></li>
        <li><a href="http://php.net/manual/en/book.mbstring.php">mbstring</a></li>
        <li><a href="http://php.net/manual/en/book.mysqlnd.php">mysqlnd</a></li>
        <li><a href="http://php.net/manual/en/book.openssl.php">openssl</a></li>
        <li><a href="http://php.net/manual/en/book.pcntl.php">pcntl</a></li>
        <li><a href="http://php.net/manual/en/book.pcre.php">pcre</a></li>
        <li><a href="http://php.net/manual/en/book.PDO.php">PDO</a></li>
        <li><a href="http://php.net/manual/en/book.pdo_sqlite.php">pdo_sqlite</a></li>
        <li><a href="http://php.net/manual/en/book.Phar.php">Phar</a></li>
        <li><a href="http://php.net/manual/en/book.posix.php">posix</a></li>
        <li><a href="http://php.net/manual/en/book.readline.php">readline</a></li>
        </ul>
      </td>
      <td align="left" valign="top">
        <ul>
        <li><a href="http://php.net/manual/en/book.Reflection.php">Reflection</a></li>
        <li><a href="http://php.net/manual/en/book.session.php">session</a></li>
        <li><a href="http://php.net/manual/en/book.SimpleXML.php">SimpleXML</a></li>
        <li><a href="http://php.net/manual/en/book.sodium.php">sodium</a></li>
        <li><a href="http://php.net/manual/en/book.SPL.php">SPL</a></li>
        <li><a href="http://php.net/manual/en/book.sqlite3.php">sqlite3</a></li>
        <li><a href="http://php.net/manual/en/book.standard.php">standard</a></li>
        <li><a href="http://php.net/manual/en/book.tokenizer.php">tokenizer</a></li>
        <li><a href="http://php.net/manual/en/book.xml.php">xml</a></li>
        <li><a href="http://php.net/manual/en/book.xmlreader.php">xmlreader</a></li>
        <li><a href="http://php.net/manual/en/book.xmlwriter.php">xmlwriter</a></li>
        <li><a href="http://php.net/manual/en/book.zlib.php">zlib</a></li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>

### Disabled, but can be enabled.
- **[OPCache](http://php.net/manual/en/book.opcache.php)** - OPcache improves PHP performance by storing precompiled script bytecode in shared memory, thereby removing the need for PHP to load and parse scripts on each request.
- **[intl](http://php.net/manual/en/intro.intl.php)** - Internationalization extension (referred as Intl) is a wrapper for » ICU library, enabling PHP programmers to perform various locale-aware operations.
- **[APCu](http://php.net/manual/en/intro.apcu.php)** - APCu is APC stripped of opcode caching.
- **[ElastiCache php-memcached extension](https://docs.aws.amazon.com/AmazonElastiCache/latest/mem-ug/Appendix.PHPAutoDiscoverySetup.html)** - 
- **[phpredis](https://github.com/phpredis/phpredis)** -  The phpredis extension provides an API for communicating with the Redis key-value store. 
- **[PostgreSQL PDO Driver](http://php.net/manual/en/ref.pdo-pgsql.php)** -  PDO_PGSQL is a driver that implements the PHP Data Objects (PDO) interface to enable access from PHP to PostgreSQL databases.
- **[MySQL PDO Driver](http://php.net/manual/en/ref.pdo-mysql.php)** -  PDO_MYSQL is a driver that implements the PHP Data Objects (PDO) interface to enable access from PHP to MySQL databases.
- **[Mongodb](http://php.net/manual/en/set.mongodb.php)** - Unlike the mongo extension, this extension is developed atop the » libmongoc and » libbson libraries. It provides a minimal API for core driver functionality: commands, queries, writes, connection management, and BSON serialization.
- **[pthreads](http://php.net/manual/en/book.pthreads.php)** - pthreads is an object-orientated API that provides all of the tools needed for multi-threading in PHP. PHP applications can create, read, write, execute and synchronize with Threads, Workers and Threaded objects.

You can enable these extensions by loading them in your project `php/config.d/php.ini`, for example:

```ini
zend_extension=opcache.so
extension=intl.so
extension=apcu.so
extension=amazon-elasticache-cluster-client.so
extension=redis.so
extension=pdo_pgsql.so
extension=pdo_mysql.so
extension=mongodb.so
extension=pthreads.so
```

## Other Extensions
If you need an extension that is not available in the layer, you will need to build the extension (and any required libraries) against the PHP binary and libraries in the Bref Layer. Then you can either include it in your own layer, loaded after the Bref layer, or you could simply package the `extensions.so` in your project and add to PHP by setting `extension=/var/task/extension.so` _(must be a an absolute path)_ in your `php/config.d/php.ini`

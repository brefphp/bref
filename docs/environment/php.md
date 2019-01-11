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
        <li>[ctype](http://php.net/manual/en/intro.ctype.php)</li>
        <li>[curl](http://php.net/manual/en/book.curl.php)</li>
        <li>date</li>
        <li>[dom](http://php.net/manual/en/book.dom.php)</li>
        <li>[exif](http://php.net/manual/en/book.exif.php)</li>
        <li>[fileinfo](http://php.net/manual/en/book.fileinfo.php)</li>
        <li>[filter](http://php.net/manual/en/book.filter.php)</li>
        <li>[ftp](http://php.net/manual/en/book.ftp.php)</li>
        <li>[gettext](http://php.net/manual/en/book.gettext.php)</li>
        <li>[hash](http://php.net/manual/en/book.hash.php)</li>
        <li>[iconv](http://php.net/manual/en/book.iconv.php)</li>
        </ul>
      </td>
      <td  align="left" valign="top">
        <ul>
        <li>[json](http://php.net/manual/en/book.json.php)</li>
        <li>[libxml](http://php.net/manual/en/book.libxml.php)</li>
        <li>[mbstring]http://php.net/manual/en/book.mbstring.php</li>
        <li>[mysqlnd](http://php.net/manual/en/book.mysqlnd.php)</li>
        <li>[openssl](http://php.net/manual/en/book.openssl.php</li>
        <li>[pcntl](http://php.net/manual/en/book.pcntl.php)</li>
        <li>[pcre](http://php.net/manual/en/book.pcre.php)</li>
        <li>[PDO](http://php.net/manual/en/book.PDO.php)</li>
        <li>[pdo_sqlite](http://php.net/manual/en/book.pdo_sqlite.php)</li>
        <li>[Phar](http://php.net/manual/en/book.Phar.php)</li>
        <li>[posix](http://php.net/manual/en/book.posix.php)</li>
        <li>[readline](http://php.net/manual/en/book.readline.php)</li>
        </ul>
      </td>
      <td align="left" valign="top">
        <ul>
        <li>[Reflection](http://php.net/manual/en/book.Reflection.php)</li>
        <li>[session](http://php.net/manual/en/book.session.php)</li>
        <li>[SimpleXML](http://php.net/manual/en/book.SimpleXML.php)</li>
        <li>[sodium](http://php.net/manual/en/book.sodium.php)</li>
        <li>[SPL](http://php.net/manual/en/book.SPL.php)</li>
        <li>[sqlite3](http://php.net/manual/en/book.sqlite3.php)</li>
        <li>[standard](http://php.net/manual/en/book.standard.php)</li>
        <li>[tokenizer](http://php.net/manual/en/book.tokenizer.php)</li>
        <li>[xml](http://php.net/manual/en/book.xml.php)</li>
        <li>[xmlreader](http://php.net/manual/en/book.xmlreader.php)</li>
        <li>[xmlwriter](http://php.net/manual/en/book.xmlwriter.php)</li>
        <li>[zlib](http://php.net/manual/en/book.zlib.php)</li>
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
extension=opcache
extension=intl
extension=apcu
extension=amazon-elasticache-cluster-client.so
extension=redis
extension=pdo_pgsql
extension=pdo_mysql
extension=mongodb
extension=pthreads
```

## Other Extensions
If you need an extension that is not available in the layer, you will need to build the extension (and any required libraries) against the PHP binary and libraries in the Bref Layer. Then you can either include it in your own layer, loaded after the Bref layer, or you could simply package the `extensions.so` in your project and add to PHP by setting `extension=/var/task/extension.so` _(must be a an absolute path)_ in your `php/config.d/php.ini`
---
title: Configuring PHP
current_menu: php
introduction: Configure PHP versions, extensions and options for your serverless application using Bref.
---

## php.ini

PHP will read its configuration from:

- `/opt/bref/etc/php/php.ini` (PHP's official production configuration)
- `/opt/bref/etc/php/conf.d/bref.ini` (Bref's optimizations for Lambda)

These files *cannot be customized*.

### Customizing php.ini

You can create your own `php.ini` to customize PHP's configuration:

1. create a `php/conf.d/` subdirectory in your project
1. create a `php.ini` file inside that directory _(the name of the file does not matter, it must have an `.ini` extensions)_

PHP will automatically include any `*.ini` file found in `php/conf.d/` in your project.

### Customizing php.ini using a custom path

If you want PHP to scan a different directory than `php/conf.d/` in your project, you can override the path by setting it in the [`PHP_INI_SCAN_DIR`](http://php.net/manual/fr/configuration.file.php#configuration.file.scan) environment variable.

> `PHP_INI_SCAN_DIR` must contain an absolute path. Since your code is placed in `/var/task` on AWS Lambda, the environment variable should contain something like `/var/task/my/different/dir`.

Learn how to declare environment variables by reading the [Environment Variables](variables.md) guide.

### Customizing php.ini in extra layers

If you are using Lambda layers, for example to use custom PHP extensions, you can override the default `php.ini` by placing your own configuration file in `/opt/bref/etc/php/conf.d/`.

Make sur to give a unique name to your `.ini` file to avoid any collision with other layers.

## Extensions

Bref strives to include the most common PHP extensions. If a major PHP extension is missing please open an issue to discuss it.

### Extensions installed and enabled

<table>
  <tbody>
    <tr>
      <td  align="left" valign="top">
        <ul>
        <li>Core</li>
        <li><a href="http://php.net/manual/en/book.bc.php">bcmath</a></li>
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
        <li><a href="http://php.net/manual/en/book.json.php">json</a></li>
        <li><a href="http://php.net/manual/en/book.libxml.php">libxml</a></li>
        </ul>
      </td>
      <td  align="left" valign="top">
        <ul>
        <li><a href="http://php.net/manual/en/book.mbstring.php">mbstring</a></li>
        <li><a href="http://php.net/manual/en/book.mysqli.php">mysqli</a></li>
        <li><a href="http://php.net/manual/en/book.mysqlnd.php">mysqlnd</a></li>
        <li><a href="http://php.net/manual/en/book.opcache.php">opcache</a></li>
        <li><a href="http://php.net/manual/en/book.openssl.php">openssl</a></li>
        <li><a href="http://php.net/manual/en/book.pcntl.php">pcntl</a></li>
        <li><a href="http://php.net/manual/en/book.pcre.php">pcre</a></li>
        <li><a href="http://php.net/manual/en/book.PDO.php">PDO</a></li>
        <li><a href="http://php.net/manual/en/book.pdo_sqlite.php">pdo_sqlite</a></li>
        <li><a href="http://php.net/manual/en/ref.pdo-mysql.php">pdo_mysql</a></li>
        <li><a href="http://php.net/manual/en/book.Phar.php">Phar</a></li>
        <li><a href="http://php.net/manual/en/book.posix.php">posix</a></li>
        <li><a href="http://php.net/manual/en/book.readline.php">readline</a></li>
        <li><a href="http://php.net/manual/en/book.Reflection.php">Reflection</a></li>
        <li><a href="http://php.net/manual/en/book.session.php">session</a></li>
        </ul>
      </td>
      <td align="left" valign="top">
        <ul>  
        <li><a href="http://php.net/manual/en/book.SimpleXML.php">SimpleXML</a></li>
        <li><a href="http://php.net/manual/en/book.sodium.php">sodium</a></li>
        <li><a href="http://php.net/manual/en/book.soap.php">SOAP</a></li>
        <li><a href="http://php.net/manual/en/book.sockets.php">sockets</a></li>
        <li><a href="http://php.net/manual/en/book.SPL.php">SPL</a></li>
        <li><a href="http://php.net/manual/en/book.sqlite3.php">sqlite3</a></li>
        <li><a href="http://php.net/manual/en/book.standard.php">standard</a></li>
        <li><a href="http://php.net/manual/en/book.tokenizer.php">tokenizer</a></li>
        <li><a href="http://php.net/manual/en/book.xml.php">xml</a></li>
        <li><a href="http://php.net/manual/en/book.xmlreader.php">xmlreader</a></li>
        <li><a href="http://php.net/manual/en/book.xmlwriter.php">xmlwriter</a></li>
        <li><a href="http://php.net/manual/en/book.xsl.php">xsl</a></li>
        <li><a href="http://php.net/manual/en/book.zlib.php">zlib</a></li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>

### Extensions installed but disabled by default

- **[intl](http://php.net/manual/en/intro.intl.php)** - Internationalization extension (referred as Intl) is a wrapper for ICU library, enabling PHP programmers to perform various locale-aware operations.
- **[APCu](http://php.net/manual/en/intro.apcu.php)** - APCu is APC stripped of opcode caching.
- **[phpredis](https://github.com/phpredis/phpredis)** -  The phpredis extension provides an API for communicating with the Redis key-value store. 
- **[PostgreSQL PDO Driver](http://php.net/manual/en/ref.pdo-pgsql.php)** -  PDO_PGSQL is a driver that implements the PHP Data Objects (PDO) interface to enable access from PHP to PostgreSQL databases.
- **[Mongodb](http://php.net/manual/en/set.mongodb.php)** - Unlike the mongo extension, this extension is developed atop the » libmongoc and » libbson libraries. It provides a minimal API for core driver functionality: commands, queries, writes, connection management, and BSON serialization.
- **[pthreads](http://php.net/manual/en/book.pthreads.php)** - pthreads is an object-orientated API that provides all of the tools needed for multi-threading in PHP. PHP applications can create, read, write, execute and synchronize with Threads, Workers and Threaded objects.
- **[imagick](http://php.net/manual/en/book.imagick.php)** - imagick is an image processing library.
- **[GD](http://php.net/manual/en/book.image.php)** - GD is an image processing library.

You can enable these extensions by loading them in `php/conf.d/php.ini` (as mentioned in [the section above](#phpini)), for example:

```ini
extension=intl
extension=apcu
extension=redis
extension=pdo_pgsql
extension=mongodb
extension=pthreads
extension=imagick
extension=gd
```

### Extra extensions

Due to space limitations in AWS Lambda, Bref cannot provide every possible extension. 
There are a list of additional PHP extensions that can be installed as a separate 
layer. They are less common extensions or extensions that for some reasons should 
not be in the normal Bref layer.

All extra PHP extensions are found in [brefphp/extra-php-extensions](https://github.com/brefphp/extra-php-extensions).

Contributions to add more PHP extensions are welcomed. 

### Custom extensions

It is also possible to provide your own extensions via [custom AWS Lambda layers](https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html).

> This guide is really raw, feel free to contribute to improve it.

To create your custom layer, you will need to:

- compile the extension (and any required libraries) in the same environment as AWS Lambda and Bref
- include the compiled extension (and required libraries) in a layer
- upload the layer to AWS Lambda
- include it in your project **after the Bref layer**
- enable the extension in a custom `php.ini`

To compile the extension, Bref provides the `bref/build-php-*` Docker images. Here is an example with Blackfire:

```dockerfile
FROM bref/build-php-73

RUN curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/amd64/7.3 \
    && mkdir -p /tmp/blackfire \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
    && cp /tmp/blackfire/blackfire-*.so /tmp/blackfire.so

# Build the final image from the lambci image that is close to the production environment
FROM lambci/lambda:provided

# Copy things we installed to the final image
COPY --from=0 /tmp/blackfire.so /opt/bref-extra/blackfire.so
```

The `.so` extension file can then be retrieved in `/opt/bref-extra/blackfire.so`. 
If you installed system libraries, you may also need to copy them to the `lambci/lambda`
image.  

See [brefphp/extra-php-extensions](https://github.com/brefphp/extra-php-extensions)
for more examples. 

Global Search in Moodle
=======================

You have two options to use as engine for Global Search:

* The easier one is to use Elasticsearch. You will only need to download java and Elasticsearch bin. Follow [ElasticSearch install instructions](https://www.elastic.co/guide/en/elasticsearch/reference/current/_installation.html).
 * The Elasticsearch engine do not need any php extensions except curl, that is already needed by Moodle
 * After downloading Elasticsearch and get it running, go to your Moodle website and enable Global Search and configure it to point to your Elasticsearch running service.
* If you do not want (or cannot) use Elasticsearch, you can follow the instructions below to download Solr and it's extensions to PHP language.

Using Apache Solr PHP extension
-------------------------------
If you do not have php-pecl-solr extension installed, please follow the steps below or refer to [Global Search](http://docs.moodle.org/dev/Global_search) docs.

Downloading PHP Solr extension
------------------------------
Please use PECL Solr Extension 1.x for Solr Server 3.x, or use PECL Solr 2.x for Solr Server 4.0+

You can download the official latest versions from [http://pecl.php.net/package/solr](http://pecl.php.net/package/solr)

Installing the downloaded PHP Solr extension
--------------------------------------------
For using Global Search, users will have to install the PHP Solr PECL extension on server. Users will have the option of configuring Solr version in  Global Search.
Following is the procedure for installing the downloaded extension in UNIX:

There are two dependencies of the extension:

* CURL extension (libcurl 7.15.0 or later is required)
* LIBXML extension (libxml2 2.6.26 or later is required)

On Debian and derivatives you can simply execute:
	`apt-get install libxml2-dev libcurl4-openssl-dev`

After installing the above dependencies, you will need to restart your apache server by executing `service apache2 restart`.

* `cd /your-downloaded-or-cloned-directory/`
* `phpize`
(This a shell script used to prepare the build environment for a php extension to be compiled. If you don't have `phpize`, you can install it by executing `sudo apt-get install php5-dev`)
* sudo make && sudo make install

The above procedure will compile and install it in the `extension_dir` directory in the `php.ini` file. To enable, the installed extension, you could follow any of the following two steps:

* Navigate to the directory `/etc/php5/conf.d` and create a new `solr.ini` file with the following line:

    extension=solr.so

    OR

* Open your `php.ini` file and include the following line:

    extension=solr.so

You may follow any of the above two steps. You will need to restart your apache server after that by executing `sudo service apache2 restart`

You can now view the `solr` extension details by clicking `PHP info` from Site administration > Server in Moodle or execute `php -m` in Terminal (`Ctrl+Alt+T`)

Download and Installation - OSX using macports
----------------------------------------------
This method provides an easy install of php solr extension without any downloads.(php solr extension version: `<=1.0.2`)

 * sudo port install apache-solr4
 * sudo port install php54-solr

You can choose your relevant available [versions here](http://www.macports.org/ports.php?by=name&substr=solr).

Setting up Global Search after installing php-solr extension
------------------------------------------------------------

After installing the php-pecl-solr extension, users will have to download the required [Apache Solr](http://lucene.apache.org/solr/) release:

* version 4.x for solr-php extension version `2.x.x`, OR
* version 3.x for solr-php extension version `1.x.x`

unzip it and keep it in an external directory of Moodle.

Users will have to replace `solconfig.xml` and `schema.xml` inside the downloaded directory `/example/solr/conf` with the ones that Global Search will provide in `/search/solr/conf/` directory.

Once the files have been copied and replaced, users will have to start the java jetty server `start.jar` located in downloaded `/example/` directory by executing `java -jar start.jar`.

For the production setup you may prefer to [run solr on tomcat 6 or 7](http://jmuras.com/blog/2012/setup-solr-4-tomcat-ubuntu-server-12-04-lts) and Ubuntu server.

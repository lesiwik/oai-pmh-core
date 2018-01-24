# OAI-PMH metadata provider core

Implementation of the core functionalities for an OAI-PMH 2.0 Data Provider written in PHP

## About

The core functionalities by themselves do not provide metadata. They need to be used along with a client application in order to provide OAI-PHM data from a specific collection. Check the [oai-pmh-demo-client](https://github.com/fccn/oai-pmh-demo-client) project to see how to create a client.

This implementation completely complies to OAI-PMH 2.0, including the support of on-the-fly output compression which may significantly reduce the amount of data being transfered.

The core functionalities are an adaptation of PHP OAI Data Provider developed by Jianfeng Li from University of Adelaide.

### Metadata formats

The following metadata formats are currently supported:
- DublinCore
- Learning Object Metadata

### Metadata sources

The metadata can be obtained from several types of sources. Each source can be included in the core as a plugin. The currently supported sources are:

- PDO (PHP Data Objects included in the PHP distribution): allows almost any popular SQL-database to be used without any change in the code. Only thing need to do is to configure	database connection and define a suitable data structure in the client configuration file.

## Structure
The project has the following structure:

* **html**\: HTML page and stylesheets with information about this project
* **src**\: source files for the core functionalities
  * **libs**\: utility classes
    * **xml_creater.php**: prepare the XML response
    * **oaidp-util.php**: general utilities for oai responses
    * **phprop.php**: configuration loader
  * **schemas**\: - metadata formats conversions
  * **sources**\: - source plugins
    * **pdo**\:	- implementations of verb responses for database sources using PDO
  * **verbs**\: 	- generic implementations of OAI-PMH requests
    * **identify.php**: identifies the data provider. Responses to *Identify*.
    * **listmetadataformats.php**: lists supported metadata formats, e.g. dc or rif-cs. Responses to *ListMetadataFormats*.
    * **listsets.php**: lists supported sets, e.g. Activity, Collection or Party. Responses to *ListSets*.
    * **listrecords.php**: lists a group of records without details. Responses to *ListRecords*. It also serves to *ListIdentifiers* which only returns identifiers.
    * **getrecord.php**: gets an individual record. Responses to *GetRecord*.

## Installation and configuration

To build a OAI-PMH 2.0 Data Provider you need to build a customized client application. You can include this library on your client project using composer:
```
composer require fccn/oai-pmh-core
```

After that, you need to manually load the library and call the *execute_request()* function with the path to the client configuration file as in the example below:

```php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/fccn/oai-pmh-core/src/main.php';

//execute request
Fccn\Oaipmh\execute_request([path-to-config-ini-file]);
```

Please check the [oai-pmh-demo-client](https://github.com/fccn/oai-pmh-demo-client) project to learn more on how to build a client application.

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/fccn/wt-translate/tags).

### v1.0
- initial working version of the core-oai-pmh project (not available on github)
- support for DublinCore and Learning Object Metadata formats

### v1.5
- attributed namespace to all classes
- improved README file and info pages
- fixed some bugs on LOM metadata aggregation
- added support for composer
- import project to github

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

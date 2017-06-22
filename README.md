<img src="http://immobiliare.github.io/ApnsPHP/images/logo.png" width="48"> ApnsPHP: Apple Push Notification & Feedback Provider
==========================

<p align="center">
	<img src="https://poser.pugx.org/duccio/apns-php/downloads">
	<img src="https://poser.pugx.org/duccio/apns-php/d/monthly">
	<img src="https://poser.pugx.org/duccio/apns-php/d/daily">
	<img src="https://poser.pugx.org/duccio/apns-php/license">
</p>

A **full set** of *open source* PHP classes to interact with the **Apple Push Notification service** for the iPhone, iPad and the iPod Touch.

- [Sample PHP Push code](sample_push.php)
- [Sample PHP Feedback code](sample_feedback.php)
- [Sample PHP Server code](sample_server.php)
- [Sample Objective-C device code](Objective-C%20Demo/)
- [Full APIs Documentation](http://immobiliare.github.io/ApnsPHP/html/index.html)
- [How to generate a Push Notification certificate and download the Entrust Root Authority certificate](Doc/CertificateCreation.md)

News
----
- **June 1, 2016**, First implementation of the HTTP/2 Protocol, please download [this package](https://github.com/immobiliare/ApnsPHP/releases/tag/v2.0.0-alpha) (please check if you have CURL with HTTP2 support built in your PHP version and generate a new certificate, you cannot use the same as binary version: [Creating a Universal Push Notification Client SSL Certificate](https://developer.apple.com/library/ios/documentation/IDEs/Conceptual/AppDistributionGuide/AddingCapabilities/AddingCapabilities.html#//apple_ref/doc/uid/TP40012582-CH26-SW11)).
- **September 29, 2015**, Some stats on this README.md, thanks [Badge Poser](https://poser.pugx.org).
- **May 12, 2015**, ApnsPHP has been moved to the [Immobiliare Labs](https://github.com/immobiliare) organization on github.
- **May 07, 2015**, ApnsPHP has increased the default payload size to 2048 and is now using the TLS protocol by default instead of the old SSL. News from Apple: https://developer.apple.com/news/?id=10222014a
- **October 26, 2012**, Project source code has moved to [github](https://github.com/immobiliare/ApnsPHP).
- **June 18, 2011**, Please, use [ApnsPHP Google Group](https://groups.google.com/group/apns-php) for help requests or to discuss about this project. To report an issue use [Issues](https://github.com/immobiliare/ApnsPHP/issues). Thanks!
- **December 18, 2010**, Full APNs message support: message body, localized action button, localized message with arguments substitution and custom launch images.
- **December 15, 2010**, Committed the first version of the Objective-C Demo Project with not-running, running in foreground and running in background application state support.
- **December 14, 2010**, Added the support for multiple Custom Property.
- **August 28, 2010**, Added support for the new APNs enhanced format that addresses some of the issues with the simple format: *Notification expiry* and *Error response*.
- **February 28, 2010**, ApnsPHP Source Code is now available.
 
Packagist
-------

https://packagist.org/packages/duccio/apns-php

Thanks @jbender!


Architecture
-------

- **Autoload system**, explicitly include only Autoload.php and all classes are loaded on-demand.
- **Message class**, to build a notification payload.
- **Push class**, to push one or more messages to Apple Push Notification service.
- **Feedback class**, to query the Apple Feedback service to get the list of broken device tokens.
- **Push Server class**, to create a Push Server with one or more (forked) processes reading from a common message queue.
- **Log class/interface**, to log to standard output or for custom logging purpose.
- **Objective-C Demo Project** with not-running, running in foreground and running in background application state support.

Classes hierarchy
------------

![](http://immobiliare.github.io/ApnsPHP/images/classes1.png)
![](http://immobiliare.github.io/ApnsPHP/images/classes2.png)
![](http://immobiliare.github.io/ApnsPHP/images/classes3.png)


Details
---------

In the Apple Push Notification Binary protocol there isn't a real-time feedback about the correctness of notifications pushed to the server. So, after each write to the server, the Push class waits for the "read stream" to change its status (or at least N microseconds); if it happened and the client socket receives an "end-of-file" from the server, the notification pushed to the server was broken, the Apple server has closed the connection and the client needs to reconnect to send other notifications still on the message queue.

To speed-up the sending activities the Push Server class can be used to create a Push Notification Server with many processes that reads a common message queue and sends parallel Push Notifications.

All client-server activities are based on the "on error, retry" pattern with customizable timeouts, retry times and retry intervals.

Requirements
-------------

PHP 5.3.0 or later with OpenSSL, PCNTL, System V shared memory and semaphore support.

```
./configure --with-openssl[=PATH] --enable-pcntl --enable-sysvshm --enable-sysvsem
```

If you plan to use only Push and Feedback provider without the Server part you need only OpenSSL (no PCNTL, System V shared memory or semaphore):

```
./configure --with-openssl[=PATH]
```

*Usually OpenSSL is built-in in standard PHP Linux distributions packages. 
Standard PHP 5.3.0 shipped with Mac OS X Snow Leopard just works.*

Please...
---------
... drop a line if you use ApnsPHP for your published application on the App Store! Thanks :-)

#Update to the Apple Push Notification Service

News from Apple: https://developer.apple.com/news/?id=10222014a

At the moment ApnsPHP is still using SSLv2 (you should set sslv3:// instead of ssl:// in Push.php to use SSLv3 or change context socket options - but only in PHP 5.6+ and, please, don't do that now!).

However if you prefer to use TLS you can change URLs in $_aServiceURLs in Push.php from "ssl://" to "tls://" and should works.

Apple has already removed SSLv3 support in the Sandbox Env. so, please, try ApnsPHP with Sandbox servers (in ssl:// or tls:// mode, if you want).

ApnsPHP
=======

ApnsPHP: Apple Push Notification &amp; Feedback Provider

More informations about this project on Google Code:

http://apns-php.googlecode.com/

Packagist
-------

https://packagist.org/packages/duccio/apns-php

Thanks @jbender! ;-)

WWDC14
-------

Ready for changes!

https://twitter.com/aldoarmiento/status/474292286570766338

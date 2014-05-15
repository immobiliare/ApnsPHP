<?php
/**
 * @file
 * sample_push_custom.php
 *
 * Custom Push demo
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://code.google.com/p/apns-php/wiki/License
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to aldo.armiento@gmail.com so we can send you a copy immediately.
 *
 * @author (C) 2010 Aldo Armiento (aldo.armiento@gmail.com)
 * @version $Id$
 */

// Adjust to your timezone
date_default_timezone_set('Europe/Rome');

// Report all PHP errors
error_reporting(-1);

// Using Autoload all classes are loaded on-demand
require_once 'ApnsPHP/Autoload.php';

// Instanciate a new ApnsPHP_Push object
$push = new ApnsPHP_Push(
	ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
	'server_certificates_bundle_sandbox.pem'
);

// Set the Root Certificate Autority to verify the Apple remote peer
$push->setRootCertificationAuthority('entrust_root_certification_authority.pem');

// Connect to the Apple Push Notification Service
$push->connect();

// Instantiate a new Message with a single recipient
$message = new ApnsPHP_Message_Custom('1e82db91c7ceddd72bf33d74ae052ac9c84a065b35148ac401388843106a7485');

// Set a custom identifier. To get back this identifier use the getCustomIdentifier() method
// over a ApnsPHP_Message object retrieved with the getErrors() message.
$message->setCustomIdentifier("Message-Badge-3");

// Set badge icon to "3"
$message->setBadge(3);

// Set a simple welcome text
$message->setText('Hello APNs-enabled device!');

// Play the default sound
$message->setSound();

// Set a custom property
$message->setCustomProperty('acme2', array('bang', 'whiz'));

// Set the expiry value to 30 seconds
$message->setExpiry(30);

// Set the "View" button title.
$message->setActionLocKey('Show me!');

// Set the alert-message string and variable string values to appear in place of the format specifiers.
$message->setLocKey('Hello %1$@, you have %2$@ new messages!'); // This will overwrite the text specified with setText() method.
$message->setLocArgs(array('Steve', 5));

// Set the filename of an image file in the application bundle.
$message->setLaunchImage('DefaultAlert.png');

// Add the message to the message queue
$push->add($message);

// Send all messages in the message queue
$push->send();

// Disconnect from the Apple Push Notification Service
$push->disconnect();

// Examine the error message container
$aErrorQueue = $push->getErrors();
if (!empty($aErrorQueue)) {
	var_dump($aErrorQueue);
}

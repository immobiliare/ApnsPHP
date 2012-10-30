<?php
/**
 * @file
 * sample_push.php
 *
 * Push demo
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

define('VALID_TOKEN', '1e82db91c7ceddd72bf33d74ae052ac9c84a065b35148ac401388843106a7485');
define('INVALID_TOKEN', 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff');

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

// Increase write interval to 100ms (default value is 10ms).
// This is an example value, the 10ms default value is OK in most cases.
// To speed up the sending operations, use Zero as parameter but
// some messages may be lost.
// $push->setWriteInterval(100 * 1000);

// Connect to the Apple Push Notification Service
$push->connect();

for ($i = 1; $i <= 10; $i++) {
	// Instantiate a new Message with a single recipient
	$message = new ApnsPHP_Message($i == 5 ? INVALID_TOKEN : VALID_TOKEN);

	// Set a custom identifier. To get back this identifier use the getCustomIdentifier() method
	// over a ApnsPHP_Message object retrieved with the getErrors() message.
	$message->setCustomIdentifier(sprintf("Message-Badge-%03d", $i));

	// Set badge icon to "3"
	$message->setBadge($i);

	// Add the message to the message queue
	$push->add($message);
}

// Send all messages in the message queue
$push->send();

// Disconnect from the Apple Push Notification Service
$push->disconnect();

// Examine the error message container
$aErrorQueue = $push->getErrors();
if (!empty($aErrorQueue)) {
	var_dump($aErrorQueue);
}

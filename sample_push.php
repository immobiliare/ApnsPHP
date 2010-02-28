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
 * @version $Id$
 */

// Adjust to your timezone
date_default_timezone_set('Europe/Rome');

// Report all PHP errors
error_reporting(-1);

// Using Autoload all classes are loaded on-demand
require_once 'ApnsPHP/Autoload.php';

// Instanciate a new ApnsPHP_Push object
$Push = new ApnsPHP_Push(
	ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
	'server_cerificates_bundle_sandbox.pem'
);

// Set the Root Certificate Autority to verify the Apple remote peer
$Push->setRootCertificationAuthority('entrust_root_certification_authority.pem');

// Connect to the Apple Push Notification Service
$Push->connect();

// Instantiate a new Message with a single recipient
$Message = new ApnsPHP_Message('e3434b98811836079119bbb8617373073292d045dc195e87de5765ebae5e50d7');

// Set badge icon to "3"
$Message->setBadge(3);

// Set a simple welcome text
$Message->setText('Hello APNs-enabled device!');

// Play the default sound
$Message->setSound();

// Add the message to the message queue
$Push->add($Message);

// Send all messages in the message queue
$Push->send();

// Disconnect from the Apple Push Notification Service
$Push->disconnect();

// Examine the error message queue
$aErrorQueue = $Push->getQueue();
if (!empty($aErrorQueue)) {
	var_dump($aErrorQueue);
}

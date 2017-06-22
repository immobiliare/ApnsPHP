<?php
/**
 * @file
 * sample_push_safari.php
 *
 * Safari push demo.
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
 * @author (C) 2017 Marco Rocca (marco.rocca@delitestudio.com)
 * @version $Id$
 */

// Adjust to your timezone
date_default_timezone_set('Europe/Rome');

// Report all PHP errors
error_reporting(-1);

// Using Autoload all classes are loaded on-demand
require_once 'ApnsPHP/Autoload.php';

// Instantiate a new ApnsPHP_Push object
$push = new ApnsPHP_Push(
	ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
	'server_certificates_bundle_sandbox.pem'
);

// Set the Provider Certificate passphrase
// $push->setProviderCertificatePassphrase('test');

// Set the Root Certificate Autority to verify the Apple remote peer
$push->setRootCertificationAuthority('entrust_root_certification_authority.pem');

// Connect to the Apple Push Notification Service
$push->connect();

// Instantiate a new Safari message with a single recipient
$message = new ApnsPHP_Message_Safari('1e82db91c7ceddd72bf33d74ae052ac9c84a065b35148ac401388843106a7485');

// Set the title of the notification.
$message->setTitle('Flight A998 Now Boarding');

// Set the body of the notification.
$message->setText('Boarding has begun for Flight A998.');

// Set the label of the action button, if the user sets the notifications to appear as alerts.
// This label should be succinct, such as "Details" or "Read more". If omitted, the default value is "Show".
$message->setAction('View');

// Set an array of values that are paired with the placeholders inside the urlFormatString value of your website.json file
$message->setUrlArgs(array('boarding', 'A998'));

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

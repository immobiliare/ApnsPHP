<?php
/**
 * @file
 * sample_feedback.php
 *
 * Feedback demo
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

// Instanciate a new ApnsPHP_Feedback object
$feedback = new ApnsPHP_Feedback(
	ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
	'server_certificates_bundle_sandbox.pem'
);

// Connect to the Apple Push Notification Feedback Service
$feedback->connect();

$aDeviceTokens = $feedback->receive();
if (!empty($aDeviceTokens)) {
	var_dump($aDeviceTokens);
}

// Disconnect from the Apple Push Notification Feedback Service
$feedback->disconnect();

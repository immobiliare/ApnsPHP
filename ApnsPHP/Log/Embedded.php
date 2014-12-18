<?php
/**
 * @file
 * ApnsPHP_Log_Embedded class definition.
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

/**
 * A simple logger.
 *
 * This simple logger implements the Log Interface and is the default logger for
 * all ApnsPHP_Abstract based class.
 *
 * This simple logger outputs The Message to standard output prefixed with date,
 * service name (ApplePushNotificationService) and Process ID (PID).
 *
 * @ingroup ApnsPHP_Log
 */
class ApnsPHP_Log_Embedded implements ApnsPHP_Log_Interface
{
    const
        STATUS  = 0,
        INFO    = 1,
        WARNING = 2,
        ERROR   = 3;

    protected $logLevelDescriptions = array(
        self::STATUS  => 'STATUS',
        self::INFO    => 'INFO',
        self::WARNING => 'WARNING',
        self::ERROR   => 'ERROR',
    );

    protected $logLevel = 0;

	/**
	 * Logs a message.
	 *
	 * @param  $sMessage @type string The message.
	 * @param  $nLevel   @type int    The log level.
	 */
	public function log($sMessage, $nLevel)
	{
       if ($nLevel < $this->logLevel) return;

		printf("%s ApnsPHP[%d]: %s: %s\n",
			date('r'), getmypid(), $this->logLevelDescriptions[$nLevel], trim($sMessage)
		);
	}

	/**
	 * Set the minimum log level of messages that should be logged.
	 */
	public function getLogLevel()
	{
	    return $this->logLevel;
	}

	/**
	 * Sets the minimum log level of messages that should be logged.
	 *
	 * @param  $nLevel @type int The log level.
	 */
	public function setLogLevel($nLevel)
	{
	    if (!isset($this->logLevelDescriptions[$nLevel])) {
            throw new ApnsPHP_Exception('Unknown Log Level: ' . $nLevel);
	    }

	    $this->logLevel = $nLevel;
	}

	/**
	 * Logs a status message.
	 *
	 * @param  $sMessage @type string The message.
	 */
	public function status($sMessage)
	{
	    $this->log($sMessage, self::STATUS);
	}

	/**
	 * Logs an info message.
	 *
	 * @param  $sMessage @type string The message.
	 */
	public function info($sMessage)
	{
	    $this->log($sMessage, self::INFO);
	}

	/**
	 * Logs a warning message.
	 *
	 * @param  $sMessage @type string The message.
	 */
	public function warning($sMessage)
	{
	    $this->log($sMessage, self::WARNING);
	}

	/**
	 * Logs an error message.
	 *
	 * @param  $sMessage @type string The message.
	 */
	public function error($sMessage)
	{
	    $this->log($sMessage, self::ERROR);
	}
}

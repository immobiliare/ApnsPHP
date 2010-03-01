<?php
/**
 * @file
 * ApnsPHP_Push class definition.
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

/**
 * @defgroup ApnsPHP_Push Push
 * @ingroup ApplePushNotificationService
 */

/**
 * The Push Notification Provider.
 * 
 * The class manages a message queue and sends notifications payload to Apple Push
 * Notification Service.
 * 
 * @ingroup ApnsPHP_Push
 */ 
class ApnsPHP_Push extends ApnsPHP_Abstract
{
	const COMMAND_PUSH = 0; /**< @type integer Payload command. */
	
	protected $_nSendRetryTimes = 3; /**< @type integer Send retry times. */
	
	protected $_aServiceURLs = array(
		'ssl://gateway.push.apple.com:2195', // Production environment
		'ssl://gateway.sandbox.push.apple.com:2195' // Sandbox environment
	); /**< @type string Service URLs environments. */

	protected $_aMessageQueue = array(); /**< @type array Message queue. */

	/**
	 * Set the send retry times value.
	 * 
	 * If the client is unable to send a payload to to the server retries at least
	 * for this value. The default send retry times is 3.
	 *
	 * @param  $nRetryTimes @type integer Send retry times.
	 */
	public function setSendRetryTimes($nRetryTimes)
	{
		$this->_nSendRetryTimes = (int)$nRetryTimes;
	}

	/**
	 * Get the send retry time value.
	 *
	 * @return @type integer Send retry times.
	 */
	public function getSendRetryTimes()
	{
		return $this->_nSendRetryTimes;
	}

	/**
	 * Adds a message to the message queue.
	 *
	 * @param  $message @type ApnsPHP_Message The message.
	 */
	public function add(ApnsPHP_Message $message)
	{
		$sMessagePayload = $message->getPayload();
		$nRecipients = $message->getRecipientsNumber();
		
		for ($i = 0; $i < $nRecipients; $i++) { 
			$this->_aMessageQueue[] = array(
				'MESSAGE' => $message,
				'BINARY_NOTIFICATION' => $this->_getBinaryNotification(
					$message->getRecipient($i),
					$sMessagePayload
				),
				'RETRY_TIMES' => 0
			);
		}
	}

	/**
	 * Sends all messages in the message queue to Apple Push Notification Service.
	 *
	 * @throws ApnsPHP_Push_Exception if not connected to the
	 *         service or no notification queued.
	 */
	public function send()
	{
		if (!$this->_hSocket) {
			throw new ApnsPHP_Push_Exception(
				'Not connected to Push Notification Service'
			);
		}

		if (empty($this->_aMessageQueue)) {
			throw new ApnsPHP_Push_Exception(
				'No notifications queued to be sent'
			);
		}

		foreach($this->_aMessageQueue as $k => &$aMessage) {
			$bSuccessfulSent = false;
			while ($bSuccessfulSent == false && $aMessage['RETRY_TIMES'] <= $this->_nSendRetryTimes) {
				if (function_exists('pcntl_signal_dispatch')) {
					pcntl_signal_dispatch();
				}
				if ($aMessage['RETRY_TIMES'] > 0) {
					$this->_log(
						'INFO: Retrying to send message ' . ($k+1) . " (" .
						"{$aMessage['RETRY_TIMES']}/" . $this->_nSendRetryTimes . ')...'
					);
				}
				
				$aMessage['RETRY_TIMES']++;
				
				$nLen = strlen($aMessage['BINARY_NOTIFICATION']);
				if ($nLen !== ($nWritten = (int)@fwrite($this->_hSocket, $aMessage['BINARY_NOTIFICATION']))) {
					$this->_log("ERROR: Unable to send message. Written {$nWritten} bytes instead of {$nLen} bytes");
					continue;
				}

				$bSuccessfulSent = true;

				$read = array($this->_hSocket);
				$null = NULL;
				$nChangedStreams = @stream_select($read, $null, $null, 0, $this->_nSocketSelectTimeout);
				if ($nChangedStreams === false) {
					$this->_log('WARNING: Unable to wait for a stream availability.');
				} else if ($nChangedStreams > 0) {
					if (feof($this->_hSocket)) {
						$bSuccessfulSent = false;
						$this->_log('ERROR: Unable to send message ' . ($k+1) . ', stream could be no more connected.');
						$this->disconnect();
						$this->connect();
					}
				}
			}

			if ($bSuccessfulSent) {
				$this->_log('INFO: Message ' . ($k+1) . ' sent.');
				unset($this->_aMessageQueue[$k]);
			}
		}
	}

	/**
	 * Returns all messages in the message queue.
	 * 
	 * When a message is successful sent is removed from the message queue.
	 * Getting the message queue after a send operation is useful to know which
	 * messages are not delivered to the end user.
	 *
	 * @param  $bEmpty @type boolean @optional Empty message queue.
	 * @return @type array Array of messages left on the queue.
	 */
	public function getQueue($bEmpty = true)
	{
		$aRet = $this->_aMessageQueue;
		if ($bEmpty) {
			$this->_aMessageQueue = array();
		}
		return $aRet;
	}

	/**
	 * Generate a binary notification from a device token and a JSON-encoded payload.
	 * 
	 * @see http://tinyurl.com/ApplePushNotificationBinary
	 *
	 * @param  $sDeviceToken @type string The device token.
	 * @param  $sPayload @type string The JSON-encoded payload.
	 * @return @type string A binary notification.
	 */
	protected function _getBinaryNotification($sDeviceToken, $sPayload)
	{
		$nTokenLength = strlen($sDeviceToken);
		$nPayloadLength = strlen($sPayload);

		$sRet  = pack('CnH*', self::COMMAND_PUSH, self::DEVICE_BINARY_SIZE, $sDeviceToken);
		$sRet .= pack('n', $nPayloadLength);
		$sRet .= $sPayload;
		
		return $sRet;
	}
}
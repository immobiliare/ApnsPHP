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
 * @author (C) 2010 Aldo Armiento (aldo.armiento@gmail.com)
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
	const COMMAND_PUSH = 1; /**< @type integer Payload command. */

	const ERROR_RESPONSE_SIZE = 6; /**< @type integer Error-response packet size. */
	const ERROR_RESPONSE_COMMAND = 8; /**< @type integer Error-response command code. */

	const STATUS_CODE_INTERNAL_ERROR = 999; /**< @type integer Status code for internal error (not Apple). */

	protected $_aErrorResponseMessages = array(
		0   => 'No errors encountered',
		1   => 'Processing error',
		2   => 'Missing device token',
		3   => 'Missing topic',
		4   => 'Missing payload',
		5   => 'Invalid token size',
		6   => 'Invalid topic size',
		7   => 'Invalid payload size',
		8   => 'Invalid token',
		self::STATUS_CODE_INTERNAL_ERROR => 'Internal error'
	); /**< @type array Error-response messages. */

	protected $_aHTTPErrorResponseMessages = array(
		200 => 'Success',
		400 => 'Bad request',
		403 => 'There was an error with the certificate',
		405 => 'The request used a bad :method value. Only POST requests are supported',
		410 => 'The device token is no longer active for the topic',
		413 => 'The notification payload was too large',
		429 => 'The server received too many requests for the same device token',
		500 => 'Internal server error',
		503 => 'The server is shutting down and unavailable',
		self::STATUS_CODE_INTERNAL_ERROR => 'Internal error'
	); /**< @type array HTTP/2 Error-response messages. */

	protected $_nSendRetryTimes = 3; /**< @type integer Send retry times. */

	protected $_aServiceURLs = array(
		'tls://gateway.push.apple.com:2195', // Production environment
		'tls://gateway.sandbox.push.apple.com:2195' // Sandbox environment
	); /**< @type array Service URLs environments. */

	protected $_aHTTPServiceURLs = array(
		'https://api.push.apple.com:443', // Production environment
		'https://api.development.push.apple.com:443' // Sandbox environment
	); /**< @type array HTTP/2 Service URLs environments. */

	protected $_aMessageQueue = array(); /**< @type array Message queue. */
	protected $_aErrors = array(); /**< @type array Error container. */

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

		$nMessageQueueLen = count($this->_aMessageQueue);
		for ($i = 0; $i < $nRecipients; $i++) {
			$nMessageID = $nMessageQueueLen + $i + 1;
			$aMessage = array(
				'MESSAGE' => $message,
				'ERRORS' => array()
			);
			if ($this->_nProtocol === self::PROTOCOL_BINARY) {
				$aMessage['BINARY_NOTIFICATION'] = $this->_getBinaryNotification(
					$message->getRecipient($i),
					$sMessagePayload,
					$nMessageID,
					$message->getExpiry()
				);
			}
			$this->_aMessageQueue[$nMessageID] = $aMessage;
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

		$this->_aErrors = array();
		$nRun = 1;
		while (($nMessages = count($this->_aMessageQueue)) > 0) {
			$this->_log("INFO: Sending messages queue, run #{$nRun}: $nMessages message(s) left in queue.");

			$bError = false;
			foreach($this->_aMessageQueue as $k => &$aMessage) {
				if (function_exists('pcntl_signal_dispatch')) {
					pcntl_signal_dispatch();
				}

				$message = $aMessage['MESSAGE'];
				$sCustomIdentifier = (string)$message->getCustomIdentifier();
				$sCustomIdentifier = sprintf('[custom identifier: %s]', empty($sCustomIdentifier) ? 'unset' : $sCustomIdentifier);

				$nErrors = 0;
				if (!empty($aMessage['ERRORS'])) {
					foreach($aMessage['ERRORS'] as $aError) {
						if ($aError['statusCode'] == 0) {
							$this->_log("INFO: Message ID {$k} {$sCustomIdentifier} has no error ({$aError['statusCode']}), removing from queue...");
							$this->_removeMessageFromQueue($k);
							continue 2;
						} else if ($aError['statusCode'] > 1 && $aError['statusCode'] <= 8) {
							$this->_log("WARNING: Message ID {$k} {$sCustomIdentifier} has an unrecoverable error ({$aError['statusCode']}), removing from queue without retrying...");
							$this->_removeMessageFromQueue($k, true);
							continue 2;
						}
					}
					if (($nErrors = count($aMessage['ERRORS'])) >= $this->_nSendRetryTimes) {
						$this->_log(
							"WARNING: Message ID {$k} {$sCustomIdentifier} has {$nErrors} errors, removing from queue..."
						);
						$this->_removeMessageFromQueue($k, true);
						continue;
					}
				}

				$nLen = strlen($this->_nProtocol === self::PROTOCOL_HTTP ? $message->getPayload() : $aMessage['BINARY_NOTIFICATION']);
				$this->_log("STATUS: Sending message ID {$k} {$sCustomIdentifier} (" . ($nErrors + 1) . "/{$this->_nSendRetryTimes}): {$nLen} bytes.");

				$aErrorMessage = null;

				if ($this->_nProtocol === self::PROTOCOL_HTTP) {
					if (!$this->_httpSend($message, $sReply)) {
						$aErrorMessage = array(
							'identifier' => $k,
							'statusCode' => curl_getinfo($this->_hSocket, CURLINFO_HTTP_CODE),
							'statusMessage' => $sReply
						);
					}
				} else {
					if ($nLen !== ($nWritten = (int)@fwrite($this->_hSocket, $aMessage['BINARY_NOTIFICATION']))) {
						$aErrorMessage = array(
							'identifier' => $k,
							'statusCode' => self::STATUS_CODE_INTERNAL_ERROR,
							'statusMessage' => sprintf('%s (%d bytes written instead of %d bytes)',
								$this->_aErrorResponseMessages[self::STATUS_CODE_INTERNAL_ERROR], $nWritten, $nLen
							)
						);
					}
				}

				usleep($this->_nWriteInterval);

				$bError = $this->_updateQueue($aErrorMessage);
				if ($bError) {
					break;
				}
			}

			if (!$bError) {
				if ($this->_nProtocol === self::PROTOCOL_BINARY) {
					$read = array($this->_hSocket);
					$null = NULL;
					$nChangedStreams = @stream_select($read, $null, $null, 0, $this->_nSocketSelectTimeout);
					if ($nChangedStreams === false) {
						$this->_log('ERROR: Unable to wait for a stream availability.');
						break;
					} else if ($nChangedStreams > 0) {
						$bError = $this->_updateQueue();
						if (!$bError) {
							$this->_aMessageQueue = array();
						}
					} else {
						$this->_aMessageQueue = array();
					}
				} else {
					$this->_aMessageQueue = array();
				}
			}

			$nRun++;
		}
	}

	/**
	 * Send a message using the HTTP/2 API protocol.
	 *
	 * @param  $message @type ApnsPHP_Message The message.
	 * @param  $sReply @type string The reply message.
	 * @return @type xxx Xxxxx.
	 */
	private function _httpSend(ApnsPHP_Message $message, &$sReply)
	{
		$aHeaders = array('Content-Type: application/json');
		$sTopic = $message->getTopic();
		if (!empty($sTopic)) {
			$aHeaders[] = sprintf('apns-topic: %s', $sTopic);
		}

		if (!(curl_setopt_array($this->_hSocket, array(
			CURLOPT_POST => true,
			CURLOPT_URL => sprintf('%s/3/device/%s', $this->_aHTTPServiceURLs[$this->_nEnvironment], $message->getRecipient()),
			CURLOPT_HTTPHEADER => $aHeaders,
			CURLOPT_POSTFIELDS => $message->getPayload()
		)) && ($sReply = curl_exec($this->_hSocket)) !== false)) {
			return false;
		}

		return curl_getinfo($this->_hSocket, CURLINFO_HTTP_CODE) === 200;
	}

	/**
	 * Returns messages in the message queue.
	 *
	 * When a message is successful sent or reached the maximum retry time is removed
	 * from the message queue and inserted in the Errors container. Use the getErrors()
	 * method to retrive messages with delivery error(s).
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
	 * Returns messages not delivered to the end user because one (or more) error
	 * occurred.
	 *
	 * @param  $bEmpty @type boolean @optional Empty message container.
	 * @return @type array Array of messages not delivered because one or more errors
	 *         occurred.
	 */
	public function getErrors($bEmpty = true)
	{
		$aRet = $this->_aErrors;
		if ($bEmpty) {
			$this->_aErrors = array();
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
	 * @param  $nMessageID @type integer @optional Message unique ID.
	 * @param  $nExpire @type integer @optional Seconds, starting from now, that
	 *         identifies when the notification is no longer valid and can be discarded.
	 *         Pass a negative value (-1 for example) to request that APNs not store
	 *         the notification at all. Default is 86400 * 7, 7 days.
	 * @return @type string A binary notification.
	 */
	protected function _getBinaryNotification($sDeviceToken, $sPayload, $nMessageID = 0, $nExpire = 604800)
	{
		$nTokenLength = strlen($sDeviceToken);
		$nPayloadLength = strlen($sPayload);

		$sRet  = pack('CNNnH*', self::COMMAND_PUSH, $nMessageID, $nExpire > 0 ? time() + $nExpire : 0, self::DEVICE_BINARY_SIZE, $sDeviceToken);
		$sRet .= pack('n', $nPayloadLength);
		$sRet .= $sPayload;

		return $sRet;
	}

	/**
	 * Parses the error message.
	 *
	 * @param  $sErrorMessage @type string The Error Message.
	 * @return @type array Array with command, statusCode and identifier keys.
	 */
	protected function _parseErrorMessage($sErrorMessage)
	{
		return unpack('Ccommand/CstatusCode/Nidentifier', $sErrorMessage);
	}

	/**
	 * Reads an error message (if present) from the main stream.
	 * If the error message is present and valid the error message is returned,
	 * otherwhise null is returned.
	 *
	 * @return @type array|null Return the error message array.
	 */
	protected function _readErrorMessage()
	{
		$sErrorResponse = @fread($this->_hSocket, self::ERROR_RESPONSE_SIZE);
		if ($sErrorResponse === false || strlen($sErrorResponse) != self::ERROR_RESPONSE_SIZE) {
			return;
		}
		$aErrorResponse = $this->_parseErrorMessage($sErrorResponse);
		if (!is_array($aErrorResponse) || empty($aErrorResponse)) {
			return;
		}
		if (!isset($aErrorResponse['command'], $aErrorResponse['statusCode'], $aErrorResponse['identifier'])) {
			return;
		}
		if ($aErrorResponse['command'] != self::ERROR_RESPONSE_COMMAND) {
			return;
		}
		$aErrorResponse['time'] = time();
		$aErrorResponse['statusMessage'] = 'None (unknown)';
		if (isset($this->_aErrorResponseMessages[$aErrorResponse['statusCode']])) {
			$aErrorResponse['statusMessage'] = $this->_aErrorResponseMessages[$aErrorResponse['statusCode']];
		}
		return $aErrorResponse;
	}

	/**
	 * Checks for error message and deletes messages successfully sent from message queue.
	 *
	 * @param  $aErrorMessage @type array @optional The error message. It will anyway
	 *         always be read from the main stream. The latest successful message
	 *         sent is the lowest between this error message and the message that
	 *         was read from the main stream.
	 *         @see _readErrorMessage()
	 * @return @type boolean True if an error was received.
	 */
	protected function _updateQueue($aErrorMessage = null)
	{
		$aStreamErrorMessage = $this->_readErrorMessage();
		if (!isset($aErrorMessage) && !isset($aStreamErrorMessage)) {
			return false;
		} else if (isset($aErrorMessage, $aStreamErrorMessage)) {
			if ($aStreamErrorMessage['identifier'] <= $aErrorMessage['identifier']) {
				$aErrorMessage = $aStreamErrorMessage;
				unset($aStreamErrorMessage);
			}
		} else if (!isset($aErrorMessage) && isset($aStreamErrorMessage)) {
			$aErrorMessage = $aStreamErrorMessage;
			unset($aStreamErrorMessage);
		}

		$this->_log('ERROR: Unable to send message ID ' .
			$aErrorMessage['identifier'] . ': ' .
			$aErrorMessage['statusMessage'] . ' (' . $aErrorMessage['statusCode'] . ').');

		$this->disconnect();

		foreach($this->_aMessageQueue as $k => &$aMessage) {
			if ($k < $aErrorMessage['identifier']) {
				unset($this->_aMessageQueue[$k]);
			} else if ($k == $aErrorMessage['identifier']) {
				$aMessage['ERRORS'][] = $aErrorMessage;
			} else {
				break;
			}
		}

		$this->connect();

		return true;
	}

	/**
	 * Remove a message from the message queue.
	 *
	 * @param  $nMessageID @type integer The Message ID.
	 * @param  $bError @type boolean @optional Insert the message in the Error container.
	 * @throws ApnsPHP_Push_Exception if the Message ID is not valid or message
	 *         does not exists.
	 */
	protected function _removeMessageFromQueue($nMessageID, $bError = false)
	{
		if (!is_numeric($nMessageID) || $nMessageID <= 0) {
			throw new ApnsPHP_Push_Exception(
				'Message ID format is not valid.'
			);
		}
		if (!isset($this->_aMessageQueue[$nMessageID])) {
			throw new ApnsPHP_Push_Exception(
				"The Message ID {$nMessageID} does not exists."
			);
		}
		if ($bError) {
			$this->_aErrors[$nMessageID] = $this->_aMessageQueue[$nMessageID];
		}
		unset($this->_aMessageQueue[$nMessageID]);
	}
}

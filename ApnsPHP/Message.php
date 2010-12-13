<?php
/**
 * @file
 * ApnsPHP_Message class definition.
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
 * @defgroup ApnsPHP_Message Message
 * @ingroup ApplePushNotificationService
 */

/**
 * The Push Notification Message.
 *
 * The class represents a message to be delivered to an end user device.
 * Notification Service.
 *
 * @ingroup ApnsPHP_Message
 * @see http://tinyurl.com/ApplePushNotificationPayload
 */
class ApnsPHP_Message
{
	const PAYLOAD_MAXIMUM_SIZE = 256; /**< @type integer The maximum size allowed for a notification payload. */

	protected $_bAutoAdjustLongPayload = true; /**< @type boolean If the JSON payload is longer than maximum allowed size, shorts message text. */

	protected $_aDeviceTokens = array(); /**< @type array Recipients device tokens. */

	protected $_sText; /**< @type string Alert message to display to the user. */
	protected $_nBadge; /**< @type integer Number to badge the application icon with. */
	protected $_sSound; /**< @type string Sound to play. */

	protected $_aCustomProperties; /**< @type mixed Custom properties container. */

	protected $_nExpiryValue = 604800; /**< @type integer That message will expire in 604800 seconds (86400 * 7, 7 days) if not successful delivered. */

	protected $_mCustomIdentifier; /**< @type mixed Custom message identifier. */

	/**
	 * Constructor.
	 *
	 * @param  $sDeviceToken @type string @optional Recipients device token.
	 */
	public function __construct($sDeviceToken = null)
	{
		if (isset($sDeviceToken)) {
			$this->addRecipient($sDeviceToken);
		}
	}

	/**
	 * Add a recipient device token.
	 *
	 * @param  $sDeviceToken @type string Recipients device token.
	 * @throws ApnsPHP_Message_Exception if the device token
	 *         is not well formed.
	 */
	public function addRecipient($sDeviceToken)
	{
		if (!preg_match('~[a-f0-9]{64}~i', $sDeviceToken)) {
			throw new ApnsPHP_Message_Exception(
				"Invalid device token '{$sDeviceToken}'"
			);
		}
		$this->_aDeviceTokens[] = $sDeviceToken;
	}

	/**
	 * Get a recipient.
	 *
	 * @param  $nRecipient @type integer @optional Recipient number to return.
	 * @throws ApnsPHP_Message_Exception if no recipient number
	 *         exists.
	 * @return @type string The recipient token at index $nRecipient.
	 */
	public function getRecipient($nRecipient = 0)
	{
		if (!isset($this->_aDeviceTokens[$nRecipient])) {
			throw new ApnsPHP_Message_Exception(
				"No recipient at index '{$nRecipient}'"
			);
		}
		return $this->_aDeviceTokens[$nRecipient];
	}

	/**
	 * Get the number of recipients.
	 *
	 * @return @type integer Recipient's number.
	 */
	public function getRecipientsNumber()
	{
		return count($this->_aDeviceTokens);
	}

	/**
	 * Get all recipients.
	 *
	 * @return @type array Array of all recipients device token.
	 */
	public function getRecipients()
	{
		return $this->_aDeviceTokens;
	}

	/**
	 * Set the alert message to display to the user.
	 *
	 * @param  $sText @type string An alert message to display to the user.
	 */
	public function setText($sText)
	{
		$this->_sText = $sText;
	}

	/**
	 * Get the alert message to display to the user.
	 *
	 * @return @type string The alert message to display to the user.
	 */
	public function getText()
	{
		return $this->_sText;
	}

	/**
	 * Set the number to badge the application icon with.
	 *
	 * @param  $nBadge @type integer A number to badge the application icon with.
	 * @throws ApnsPHP_Message_Exception if badge is not an
	 *         integer.
	 */
	public function setBadge($nBadge)
	{
		if (!is_int($nBadge)) {
			throw new ApnsPHP_Message_Exception(
				"Invalid badge number '{$nBadge}'"
			);
		}
		$this->_nBadge = $nBadge;
	}

	/**
	 * Get the number to badge the application icon with.
	 *
	 * @return @type integer The number to badge the application icon with.
	 */
	public function getBadge()
	{
		return $this->_nBadge;
	}

	/**
	 * Set the sound to play.
	 *
	 * @param  $sSound @type string @optional A sound to play ('default sound' is
	 *         the default sound).
	 */
	public function setSound($sSound = 'default')
	{
		$this->_sSound = $sSound;
	}

	/**
	 * Get the sound to play.
	 *
	 * @return @type string The sound to play.
	 */
	public function getSound()
	{
		return $this->_sSound;
	}

	/**
	 * Set a custom property.
	 *
	 * @param  $sName @type string Custom property name.
	 * @param  $mValue @type mixed Custom property value.
	 * @throws ApnsPHP_Message_Exception if custom property name is not outside
	 *         the Apple-reserved 'aps' namespace.
	 */
	public function setCustomProperty($sName, $mValue)
	{
		if ($sName == 'aps') {
			throw new ApnsPHP_Message_Exception(
				"Property name 'aps' can not be used for custom property."
			);
		}
		$this->_aCustomProperties[trim($sName)] = $mValue;
	}

	/**
	 * Get the first custom property name.
	 *
	 * @deprecated Use 'getCustomPropertyNames' instead.
	 *
	 * @return @type string The first custom property name.
	 */
	public function getCustomPropertyName()
	{
		if (!is_array($this->_aCustomProperties)) {
			return;
		}
		$aKeys = array_keys($this->_aCustomProperties);
		return $aKeys[0];
	}

	/**
	 * Get the first custom property value.
	 *
	 * @deprecated Use 'getCustomProperty' instead.
	 *
	 * @return @type mixed The first custom property value.
	 */
	public function getCustomPropertyValue()
	{
		if (!is_array($this->_aCustomProperties)) {
			return;
		}
		$aKeys = array_keys($this->_aCustomProperties);
		return $this->_aCustomProperties[$aKeys[0]];
	}

	/**
	 * Get all custom properties names.
	 *
	 * @return @type array All properties names.
	 */
	public function getCustomPropertyNames()
	{
		if (!is_array($this->_aCustomProperties)) {
			return array();
		}
		return array_keys($this->_aCustomProperties);
	}

	/**
	 * Get the custom property value.
	 *
	 * @param  $sName @type string Custom property name.
	 * @throws Exception if no property exists with the specified name.
	 * @return @type string The custom property value.
	 */
	public function getCustomProperty($sName)
	{
		if (!array_key_exists($sName, $this->_aCustomProperties)) {
			throw new ApnsPHP_Message_Exception(
				"No property exists with the specified name '{$sName}'."
			);
		}
		return $this->_aCustomProperties[$sName];
	}

	/**
	 * Set the auto-adjust long payload value.
	 *
	 * @param  $bAutoAdjust @type boolean If true a long payload is shorted cutting
	 *         long text value.
	 */
	public function setAutoAdjustLongPayload($bAutoAdjust)
	{
		$this->_bAutoAdjustLongPayload = (boolean)$bAutoAdjust;
	}

	/**
	 * Get the auto-adjust long payload value.
	 *
	 * @return @type boolean The auto-adjust long payload value.
	 */
	public function getAutoAdjustLongPayload()
	{
		return $this->_bAutoAdjustLongPayload;
	}

	/**
	 * PHP Magic Method. When an object is "converted" to a string, JSON-encoded
	 * payload is returned.
	 *
	 * @return @type string JSON-encoded payload.
	 */
	public function __toString()
	{
		try {
			$sJSONPayload = $this->getPayload();
		} catch (ApnsPHP_Message_Exception $e) {
			$sJSONPayload = '';
		}
		return $sJSONPayload;
	}

	/**
	 * Convert the message in a JSON-encoded payload.
	 *
	 * @throws ApnsPHP_Message_Exception if payload is longer than maximum allowed
	 *         size and AutoAdjustLongPayload is disabled.
	 * @return @type string JSON-encoded payload.
	 */
	public function getPayload()
	{
		$aPayload['aps'] = array();

		if (isset($this->_sText)) {
			$aPayload['aps']['alert'] = (string)$this->_sText;
		}
		if (isset($this->_nBadge) && $this->_nBadge > 0) {
			$aPayload['aps']['badge'] = (int)$this->_nBadge;
		}
		if (isset($this->_sSound)) {
			$aPayload['aps']['sound'] = (string)$this->_sSound;
		}

		if (is_array($this->_aCustomProperties)) {
			foreach($this->_aCustomProperties as $sPropertyName => $mPropertyValue) {
				$aPayload[$sPropertyName] = $mPropertyValue;
			}
		}

		$sJSONPayload = json_encode($aPayload, JSON_FORCE_OBJECT);
		$nJSONPayloadLen = strlen($sJSONPayload);

		if ($nJSONPayloadLen > self::PAYLOAD_MAXIMUM_SIZE) {
			if ($this->_bAutoAdjustLongPayload) {
				$nTextLen = strlen($this->_sText);
				if ($nJSONPayloadLen - $nTextLen <= self::PAYLOAD_MAXIMUM_SIZE) {
					$this->_sText = substr($this->_sText, 0, $nTextLen - ($nJSONPayloadLen - self::PAYLOAD_MAXIMUM_SIZE));
					return $this->getPayload();
				} else {
					throw new ApnsPHP_Message_Exception(
						"JSON Payload is too long: {$nJSONPayloadLen} bytes. Maximum size is " .
						self::PAYLOAD_MAXIMUM_SIZE . " bytes. The message text can not be auto-adjusted."
					);
				}
			} else {
				throw new ApnsPHP_Message_Exception(
					"JSON Payload is too long: {$nJSONPayloadLen} bytes. Maximum size is " .
					self::PAYLOAD_MAXIMUM_SIZE . " bytes"
				);
			}
		}

		return $sJSONPayload;
	}

	/**
	 * Set the expiry value.
	 *
	 * @param  $nExpiryValue @type integer This message will expire in N seconds
	 *         if not successful delivered.
	 */
	public function setExpiry($nExpiryValue)
	{
		if (!is_int($nExpiryValue)) {
			throw new ApnsPHP_Message_Exception(
				"Invalid seconds number '{$nExpiryValue}'"
			);
		}
		$this->_nExpiryValue = $nExpiryValue;
	}

	/**
	 * Get the expiry value.
	 *
	 * @return @type integer The expire message value (in seconds).
	 */
	public function getExpiry()
	{
		return $this->_nExpiryValue;
	}

	/**
	 * Set the custom message identifier.
	 *
	 * The custom message identifier is useful to associate a push notification
	 * to a DB record or an User entry for example. The custom message identifier
	 * can be retrieved in case of error using the getCustomIdentifier()
	 * method of an entry retrieved by the getErrors() method.
	 * This custom identifier, if present, is also used in all status message by
	 * the ApnsPHP_Push class.
	 *
	 * @param  $mCustomIdentifier @type mixed The custom message identifier.
	 */
	public function setCustomIdentifier($mCustomIdentifier)
	{
		$this->_mCustomIdentifier = $mCustomIdentifier;
	}

	/**
	 * Get the custom message identifier.
	 *
	 * @return @type mixed The custom message identifier.
	 */
	public function getCustomIdentifier()
	{
		return $this->_mCustomIdentifier;
	}
}
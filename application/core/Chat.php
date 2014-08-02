<?php

namespace ManiaControl;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\UnknownPlayerException;

/**
 * Chat Utility Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Chat {
	/*
	 * Constants
	 */
	const SETTING_PREFIX             = 'Messages Prefix';
	const SETTING_FORMAT_INFORMATION = 'Information Format';
	const SETTING_FORMAT_SUCCESS     = 'Success Format';
	const SETTING_FORMAT_ERROR       = 'Error Format';
	const SETTING_FORMAT_USAGEINFO   = 'UsageInfo Format';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct chat utility
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_PREFIX, '» ');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_INFORMATION, '$fff');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_SUCCESS, '$0f0');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_ERROR, '$f30');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_USAGEINFO, '$f80');
	}

	/**
	 * Send an information message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @return bool
	 */
	public function sendInformation($message, $login = null, $prefix = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_INFORMATION);
		return $this->sendChat($format . $message, $login, $prefix);
	}

	/**
	 * Send a chat message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @return bool
	 */
	public function sendChat($message, $login = null, $prefix = true) {
		if (!$this->maniaControl->getClient()) {
			return false;
		}

		if (!$login) {
			$prefix      = $this->getPrefix($prefix);
			$chatMessage = '$<$z$ff0' . str_replace(' ', '', $prefix) . $prefix . $message . '$>';
			$this->maniaControl->getClient()->chatSendServerMessage($chatMessage);
		} else {
			$chatMessage = '$<$z$ff0' . $this->getPrefix($prefix) . $message . '$>';
			if (!is_array($login)) {
				$login = Player::parseLogin($login);
			}
			try {
				$this->maniaControl->getClient()->chatSendServerMessage($chatMessage, $login);
			} catch (UnknownPlayerException $e) {
			}
		}
		return true;
	}

	/**
	 * Get prefix
	 *
	 * @param string|bool $prefix
	 * @return string
	 */
	private function getPrefix($prefix) {
		if (is_string($prefix)) {
			return $prefix;
		}
		if ($prefix === true) {
			return $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_PREFIX);
		}
		return '';
	}

	/**
	 * Send an Error Message to all Connected Admins
	 *
	 * @param string $message
	 * @param int    $minLevel
	 * @param bool   $prefix
	 */
	public function sendErrorToAdmins($message, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR, $prefix = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_ERROR);
		$this->sendMessageToAdmins($format . $message, $minLevel, $prefix);
	}

	/**
	 * Send a Message to all connected Admins
	 *
	 * @param string      $message
	 * @param int         $minLevel
	 * @param bool|string $prefix
	 * @return bool
	 */
	public function sendMessageToAdmins($message, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR, $prefix = true) {
		$admins = $this->maniaControl->getAuthenticationManager()->getConnectedAdmins($minLevel);
		return $this->sendChat($message, $admins, $prefix);
	}

	/**
	 * Send a success message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param bool|string $prefix
	 * @return bool
	 */
	public function sendSuccess($message, $login = null, $prefix = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_SUCCESS);
		return $this->sendChat($format . $message, $login, $prefix);
	}

	/**
	 * Sends a Success Message to all connected Admins
	 *
	 * @param string      $message
	 * @param int         $minLevel
	 * @param bool|string $prefix
	 * @return bool
	 */
	public function sendSuccessToAdmins($message, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR, $prefix = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_SUCCESS);
		return $this->sendMessageToAdmins($format . $message, $minLevel, $prefix);
	}

	/**
	 * Send the Exception Information to the Chat
	 *
	 * @param \Exception $exception
	 * @param string     $login
	 * @return bool
	 */
	public function sendException(\Exception $exception, $login = null) {
		$message = "Exception occurred: '{$exception->getMessage()}' ({$exception->getCode()})";
		return $this->sendError($message, $login);
	}

	/**
	 * Send an Error Message to the Chat
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @return bool
	 */
	public function sendError($message, $login = null, $prefix = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_ERROR);
		return $this->sendChat($format . $message, $login, $prefix);
	}

	/**
	 * Send a Exception Message to all Connected Admins
	 *
	 * @param \Exception  $exception
	 * @param int         $minLevel
	 * @param bool|string $prefix
	 */
	public function sendExceptionToAdmins(\Exception $exception, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR,
	                                      $prefix = true) {
		$format  = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_ERROR);
		$message = $format . "Exception: '{$exception->getMessage()}' ({$exception->getCode()})";
		$this->sendMessageToAdmins($message, $minLevel, $prefix);
	}

	/**
	 * Send an usage info message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @return bool
	 */
	public function sendUsageInfo($message, $login = null, $prefix = false) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_USAGEINFO);
		return $this->sendChat($format . $message, $login, $prefix);
	}
}

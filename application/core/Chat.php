<?php

namespace ManiaControl;

use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

/**
 * Chat Utility Class
 *
 * @author steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
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
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Construct chat utility
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_PREFIX, '» ');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_FORMAT_INFORMATION, '$fff');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_FORMAT_SUCCESS, '$0f0');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_FORMAT_ERROR, '$f00');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_FORMAT_USAGEINFO, '$f80');
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
			return $this->maniaControl->settingManager->getSetting($this, self::SETTING_PREFIX);
		}
		return '';
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
		if (!$this->maniaControl->client) {
			return false;
		}
		$chatMessage = '$z$<' . $this->getPrefix($prefix) . $message . '$>$z';
		if ($login === null) {
			$this->maniaControl->client->chatSendServerMessage($chatMessage);
		} else {
			try{
			$this->maniaControl->client->chatSendServerMessage($chatMessage, $login);
			} catch(Exception $e){
				if($e->getMessage() != "Login unknown."){
					throw $e;
				}
			}
		}
		return true;
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
		$format = $this->maniaControl->settingManager->getSetting($this, self::SETTING_FORMAT_INFORMATION);
		return $this->sendChat($format . $message, $login);
	}

	/**
	 * Send a success message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @return bool
	 */
	public function sendSuccess($message, $login = null, $prefix = true) {
		$format = $this->maniaControl->settingManager->getSetting($this, self::SETTING_FORMAT_SUCCESS);
		return $this->sendChat($format . $message, $login);
	}

	/**
	 * Send an error message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @return bool
	 */
	public function sendError($message, $login = null, $prefix = true) {
		$format = $this->maniaControl->settingManager->getSetting($this, self::SETTING_FORMAT_ERROR);
		return $this->sendChat($format . $message, $login);
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
		$format = $this->maniaControl->settingManager->getSetting($this, self::SETTING_FORMAT_USAGEINFO);
		return $this->sendChat($format . $message, $login);
	}
}

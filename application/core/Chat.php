<?php

namespace ManiaControl;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\LoginUnknownException;

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
	 * Send an information message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @return bool
	 */
	public function sendInformation($message, $login = null, $prefix = true) {
		$format = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FORMAT_INFORMATION);
		return $this->sendChat($format . $message, $login, $prefix);
	}

	/**
	 * Sends a Message to all Connected Admins
	 *
	 * @param      $message
	 * @param int  $minLevel (Constant from AuthenticationManager)
	 * @param bool $prefix
	 */
	public function sendMessageToAdmins($message, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR, $prefix = true) {
		//TODO specifiy in player or adminmanager a getAdmins() with minlevel function
		foreach($this->maniaControl->playerManager->getPlayers() as $player){
			/** @var Player $player */
			if($this->maniaControl->authenticationManager->checkRight($player, $minLevel)){
				$this->sendChat($message, $player->login, $prefix);
			}
		}
	}

	/**
	 * Sends a Error Message to all Connected Admins
	 *
	 * @param      $message
	 * @param int  $minLevel (Constant from AuthenticationManager)
	 * @param bool $prefix
	 */
	public function sendErrorToAdmins($message, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR, $prefix = true) {
		$format = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FORMAT_ERROR);
		$this->sendMessageToAdmins($format . $message, $prefix);
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

		if (!$login) {
			$prefix      = $this->getPrefix($prefix);
			$chatMessage = '$<$z$ff0' . str_replace(' ', '', $prefix) . $prefix . $message . '$>';
			$this->maniaControl->client->chatSend($chatMessage, null, true);
		} else {
			$chatMessage = '$<$z$ff0' . $this->getPrefix($prefix) . $message . '$>';
			if (is_object($login) && property_exists($login, 'login')) {
				$login = $login->login;
			}
			try {
				$this->maniaControl->client->chatSend($chatMessage, $login, true);
			} catch (LoginUnknownException $e) {
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
			return $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_PREFIX);
		}
		return '';
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
		$format = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FORMAT_SUCCESS);
		return $this->sendChat($format . $message, $login, $prefix);
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
		$this->maniaControl->errorHandler->triggerDebugNotice($message);
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
		$format = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FORMAT_ERROR);
		return $this->sendChat($format . $message, $login, $prefix);
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
		$format = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_FORMAT_USAGEINFO);
		return $this->sendChat($format . $message, $login, $prefix);
	}
}

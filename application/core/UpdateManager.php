<?php

namespace ManiaControl;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Class checking for ManiaControl Core and Plugin Updates
 *
 * @author steeffeen & kremsy
 */
class UpdateManager implements CallbackListener, CommandListener {
	/**
	 * Constants
	 */
	const SETTING_ENABLEUPDATECHECK = 'Enable Automatic Core Update Check';
	const SETTING_UPDATECHECK_INTERVAL = 'Core Update Check Interval (Hours)';
	const SETTING_UPDATECHECK_CHANNEL = 'Core Update Channel (release, beta, alpha)';
	const URL_WEBSERVICE = 'http://ws.maniacontrol.com/';
	const CHANNEL_RELEASE = 'release';
	const CHANNEL_BETA = 'beta';
	const CHANNEL_ALPHA = 'alpha';
	
	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $lastUpdateCheck = -1;
	private $coreUpdateData = null;

	/**
	 * Create a new Updater
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_ENABLEUPDATECHECK, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_UPDATECHECK_INTERVAL, 24.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_UPDATECHECK_CHANNEL, self::CHANNEL_ALPHA);
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_1_MINUTE, $this, 'handle1Minute');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerJoined');
		
		// Register for chat commands
		$this->maniaControl->commandManager->registerCommandListener('checkupdate', $this, 'handle_CheckUpdate', true);
	}

	/**
	 * Handle ManiaControl 1Minute callback
	 *
	 * @param array $callback
	 */
	public function handle1Minute(array $callback) {
		$updateCheckEnabled = $this->maniaControl->settingManager->getSetting($this, self::SETTING_ENABLEUPDATECHECK);
		if (!$updateCheckEnabled) {
			// Automatic update check disabled
			if ($this->coreUpdateData) {
				$this->coreUpdateData = null;
			}
			return;
		}
		// Only check once per hour
		$updateInterval = $this->maniaControl->settingManager->getSetting($this, self::SETTING_UPDATECHECK_INTERVAL);
		if ($this->lastUpdateCheck > time() - $updateInterval * 3600.) {
			return;
		}
		$this->lastUpdateCheck = time();
		$updateData = $this->checkCoreUpdate();
		if (!$updateData) {
			// No update available
			return;
		}
		$this->maniaControl->log('New ManiaControl Version ' . $updateData->version . ' available!');
		$this->coreUpdateData = $updateData;
	}

	/**
	 * Handle ManiaControl PlayerJoined callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerJoined(array $callback) {
		if (!$this->coreUpdateData) {
			return;
		}
		// Announce available update
		$player = $callback[1];
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) return;
		$this->maniaControl->chat->sendInformation('New ManiaControl Version ' . $this->coreUpdateData->version . ' available!', 
				$player->login);
	}

	/**
	 * Handle //checkupdate command
	 *
	 * @param array $chatCallback
	 * @param Player $player
	 */
	public function handle_CheckUpdate(array $chatCallback, Player $player) {
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$updateData = $this->checkCoreUpdate();
		if (!$updateData) {
			$this->maniaControl->chat->sendInformation('No Update available!', $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess('Update for Version ' . $updateData->version . ' available!', $player->login);
	}

	/**
	 * Check given Plugin Class for Update
	 *
	 * @param string $pluginClass
	 * @return mixed
	 */
	public function checkPluginUpdate($pluginClass) {
		if (is_object($pluginClass)) {
			$pluginClass = get_class($pluginClass);
		}
		$pluginId = $pluginClass::getId();
		$url = self::URL_WEBSERVICE . 'plugins?id=' . $pluginId;
		$dataJson = file_get_contents($url);
		$pluginVersions = json_decode($dataJson);
		if (!$pluginVersions || !isset($pluginVersions[0])) {
			return false;
		}
		$pluginData = $pluginVersions[0];
		$pluginVersion = $pluginClass::getVersion();
		if ($pluginData->version <= $pluginVersion) {
			return false;
		}
		return $pluginData;
	}

	/**
	 * Check for Update of ManiaControl
	 *
	 * @return mixed
	 */
	private function checkCoreUpdate() {
		$updateChannel = $this->getCurrentUpdateChannelSetting();
		$url = self::URL_WEBSERVICE . 'versions?current=1&channel=' . $updateChannel;
		$dataJson = file_get_contents($url);
		$versions = json_decode($dataJson);
		if (!$versions || !isset($versions[0])) {
			return false;
		}
		$updateData = $versions[0];
		if ($updateData->version <= ManiaControl::VERSION) {
			return false;
		}
		return $updateData;
	}

	/**
	 * Perform a Core Update
	 *
	 * @param object $updateData
	 * @return bool
	 */
	private function performCoreUpdate($updateData = null) {
		if (!$updateData) {
			$updateData = $this->checkCoreUpdate();
			if (!$updateData) {
				return false;
			}
		}
		$updateFileContent = file_get_contents($updateData->url);
		$tempDir = ManiaControlDir . '/temp/';
		if (!is_dir($tempDir)) {
			mkdir($tempDir);
		}
		$updateFileName = $tempDir . basename($updateData->url);
		$bytes = file_put_contents($updateFileName, $updateFileContent);
		if (!$bytes || $bytes <= 0) {
			trigger_error("Couldn't save Update Zip.");
			return false;
		}
		$zip = new \ZipArchive();
		$result = $zip->open($updateFileName);
		if ($result !== true) {
			trigger_error("Couldn't open Update Zip. ({$result})");
			return false;
		}
		$zip->extractTo(ManiaControlDir . '/test/');
		$zip->close();
		return true;
	}

	/**
	 * Retrieve the Update Channel Setting
	 *
	 * @return string
	 */
	private function getCurrentUpdateChannelSetting() {
		$updateChannel = $this->maniaControl->settingManager->getSetting($this, self::SETTING_UPDATECHECK_CHANNEL);
		$updateChannel = strtolower($updateChannel);
		if (!in_array($updateChannel, array(self::CHANNEL_RELEASE, self::CHANNEL_BETA, self::CHANNEL_ALPHA))) {
			$updateChannel = self::CHANNEL_RELEASE;
		}
		return $updateChannel;
	}
}

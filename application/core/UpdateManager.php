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
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_UPDATECHECK_CHANNEL, 'alpha');
		
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
			if ($this->coreUpdateData) {
				$this->coreUpdateData = null;
			}
			return;
		}
		$updateInterval = $this->maniaControl->settingManager->getSetting($this, self::SETTING_UPDATECHECK_INTERVAL);
		if ($this->lastUpdateCheck > time() - $updateInterval * 3600.) {
			return;
		}
		$this->lastUpdateCheck = time();
		$updateData = $this->checkCoreUpdate();
		if (!$updateData) {
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
		$player = $callback[1];
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			return;
		}
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
		$updateChannel = $this->maniaControl->settingManager->getSetting($this, self::SETTING_UPDATECHECK_CHANNEL);
		$updateChannel = strtolower($updateChannel);
		if (!in_array($updateChannel, array('release', 'beta', 'alpha'))) {
			$updateChannel = 'release';
		}
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
}

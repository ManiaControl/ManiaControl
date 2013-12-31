<?php

namespace ManiaControl;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Manager checking for ManiaControl Core and Plugin Updates
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
	const SETTING_PERFORM_BACKUPS = 'Perform Backup before Updating';
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
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_PERFORM_BACKUPS, true);
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_1_MINUTE, $this, 'handle1Minute');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerJoined');
		
		// Register for chat commands
		$this->maniaControl->commandManager->registerCommandListener('checkupdate', $this, 'handle_CheckUpdate', true);
		$this->maniaControl->commandManager->registerCommandListener('coreupdate', $this, 'handle_CoreUpdate', true);
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
	 * Handle //coreupdate command
	 *
	 * @param array $chatCallback
	 * @param Player $player
	 */
	public function handle_CoreUpdate(array $chatCallback, Player $player) {
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_MASTERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$updateData = $this->checkCoreUpdate(true);
		if (!$updateData) {
			$this->maniaControl->chat->sendError('Update is currently not possible!', $player->login);
			return;
		}
		$this->maniaControl->chat->sendInformation("Starting Update to Version v{$updateData->version}...", $player->login);
		$performBackup = $this->maniaControl->settingManager->getSetting($this, self::SETTING_PERFORM_BACKUPS);
		if ($performBackup && !$this->performBackup()) {
			$this->maniaControl->chat->sendError('Creating backup failed.', $player->login);
		}
		if (!$this->performCoreUpdate($updateData)) {
			$this->maniaControl->chat->sendError('Update failed!', $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess('Update finished!', $player->login);
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
	private function checkCoreUpdate($ignoreVersion = false) {
		$updateChannel = $this->getCurrentUpdateChannelSetting();
		$url = self::URL_WEBSERVICE . 'versions?update=1&current=1&channel=' . $updateChannel;
		$dataJson = file_get_contents($url);
		$versions = json_decode($dataJson);
		if (!$versions || !isset($versions[0])) {
			return false;
		}
		$updateData = $versions[0];
		if (!$ignoreVersion && $updateData->version <= ManiaControl::VERSION) {
			return false;
		}
		return $updateData;
	}

	/**
	 * Perform a Backup of ManiaControl
	 *
	 * @return bool
	 */
	private function performBackup() {
		$backupFolder = ManiaControlDir . '/backup/';
		if (!is_dir($backupFolder)) mkdir($backupFolder);
		$backupFileName = $backupFolder . 'backup_' . ManiaControl::VERSION . '_' . date('y-m-d') . '.zip';
		$backupZip = new \ZipArchive();
		if ($backupZip->open($backupFileName, \ZipArchive::CREATE) !== TRUE) {
			trigger_error("Couldn't create Backup Zip!");
			return false;
		}
		$excludes = array('.', '..', 'backup', 'logs', 'ManiaControl.log');
		$pathInfo = pathInfo(ManiaControlDir);
		$parentPath = $pathInfo['dirname'] . '/';
		$dirName = $pathInfo['basename'];
		$backupZip->addEmptyDir($dirName);
		$this->zipDirectory($backupZip, ManiaControlDir, strlen($parentPath), $excludes);
		$backupZip->close();
		return true;
	}

	/**
	 * Add a complete Directory to the ZipArchive
	 *
	 * @param \ZipArchive $zipArchive
	 * @param string $folderName
	 * @param int $prefixLength
	 * @param array $excludes
	 * @return bool
	 */
	private function zipDirectory(\ZipArchive &$zipArchive, $folderName, $prefixLength, array $excludes = array()) {
		$folderHandle = opendir($folderName);
		if (!$folderHandle) {
			trigger_error("Couldn't open Folder '{$folderName}' for Backup!");
			return false;
		}
		while (false !== ($file = readdir($folderHandle))) {
			if (in_array($file, $excludes)) continue;
			$filePath = $folderName . '/' . $file;
			$localPath = substr($filePath, $prefixLength);
			if (is_file($filePath)) {
				$zipArchive->addFile($filePath, $localPath);
				continue;
			}
			if (is_dir($filePath)) {
				$zipArchive->addEmptyDir($localPath);
				$this->zipDirectory($zipArchive, $filePath, $prefixLength, $excludes);
				continue;
			}
		}
		closedir($folderHandle);
		return true;
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
		if (!is_dir($tempDir)) mkdir($tempDir);
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
		$zip->extractTo(ManiaControlDir);
		$zip->close();
		unlink($updateFileName);
		@rmdir($tempDir);
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

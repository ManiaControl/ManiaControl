<?php

namespace ManiaControl;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;

/**
 * Manager checking for ManiaControl Core and Plugin Updates
 *
 * @author steeffeen & kremsy
 */
class UpdateManager implements CallbackListener, CommandListener, TimerListener {
	/*
	 * Constants
	 */
	const SETTING_ENABLEUPDATECHECK      = 'Enable Automatic Core Update Check';
	const SETTING_UPDATECHECK_INTERVAL   = 'Core Update Check Interval (Hours)';
	const SETTING_UPDATECHECK_CHANNEL    = 'Core Update Channel (release, beta, nightly)';
	const SETTING_PERFORM_BACKUPS        = 'Perform Backup before Updating';
	const SETTING_AUTO_UPDATE            = 'Perform update automatically';
	const SETTING_PERMISSION_UPDATE      = 'Update Core';
	const SETTING_PERMISSION_UPDATECHECK = 'Check Core Update';
	const URL_WEBSERVICE                 = 'http://ws.maniacontrol.com/';
	const CHANNEL_RELEASE                = 'release';
	const CHANNEL_BETA                   = 'beta';
	const CHANNEL_NIGHTLY                = 'nightly';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $coreUpdateData = null;

	/**
	 * Create a new Update Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_ENABLEUPDATECHECK, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_AUTO_UPDATE, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_UPDATECHECK_INTERVAL, 1);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_UPDATECHECK_CHANNEL, self::CHANNEL_NIGHTLY); //TODO just temp until release
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_PERFORM_BACKUPS, true);

		// Register for callbacks
		$updateInterval = $this->maniaControl->settingManager->getSetting($this, self::SETTING_UPDATECHECK_INTERVAL);
		$this->maniaControl->timerManager->registerTimerListening($this, 'hourlyUpdateCheck', 1000 * 60 * 60 * $updateInterval);
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerJoined');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECTED, $this, 'autoUpdate');

		//define Permissions
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_UPDATE, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_UPDATECHECK, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		// Register for chat commands
		$this->maniaControl->commandManager->registerCommandListener('checkupdate', $this, 'handle_CheckUpdate', true);
		$this->maniaControl->commandManager->registerCommandListener('coreupdate', $this, 'handle_CoreUpdate', true);
	}

	/**
	 * Perform Hourly Update Check
	 *
	 * @param $time
	 */
	public function hourlyUpdateCheck($time) {
		$updateCheckEnabled = $this->maniaControl->settingManager->getSetting($this, self::SETTING_ENABLEUPDATECHECK);
		if (!$updateCheckEnabled) {
			// Automatic update check disabled
			if ($this->coreUpdateData) {
				$this->coreUpdateData = null;
			}
			return;
		}

		$updateData = $this->checkCoreUpdate();
		if (!$updateData) {
			return;
		}
		$this->maniaControl->log('New ManiaControl Version ' . $updateData->version . ' available!');
		$this->coreUpdateData = $updateData;

		$this->autoUpdate($time);
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
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_UPDATE)) {
			return;
		}
		$this->maniaControl->chat->sendInformation('New ManiaControl Version ' . $this->coreUpdateData->version . ' available!', $player->login);
	}


	/**
	 * Perform automatic update as soon as a the Server is empty (also every hour got checked when its empty)
	 *
	 * @param mixed $callback
	 */
	public function autoUpdate($callback) {
		$autoUpdate = $this->maniaControl->settingManager->getSetting($this, self::SETTING_AUTO_UPDATE);
		if (!$autoUpdate) {
			return;
		}

		if (count($this->maniaControl->playerManager->getPlayers()) > 0) {
			return;
		}

		$updateData = $this->checkCoreUpdate(true);
		if (!$updateData) {
			return;
		}

		$this->maniaControl->log("Starting Update to Version v{$updateData->version}...");
		$performBackup = $this->maniaControl->settingManager->getSetting($this, self::SETTING_PERFORM_BACKUPS);
		if ($performBackup && !$this->performBackup()) {
			$this->maniaControl->log("Creating Backup failed!");
		}
		$this->performCoreUpdate($updateData);
	}


	/**
	 * Handle //checkupdate command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function handle_CheckUpdate(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_UPDATECHECK)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$updateChannel = $this->maniaControl->settingManager->getSetting($this, self::SETTING_UPDATECHECK_CHANNEL);
		if ($updateChannel != self::CHANNEL_NIGHTLY) {
			// Check update and send result message
			$updateData = $this->checkCoreUpdate();
			if (!$updateData) {
				$this->maniaControl->chat->sendInformation('No Update available!', $player->login);
				return;
			}
			$this->maniaControl->chat->sendSuccess('Update for Version ' . $updateData->version . ' available!', $player->login);
		} else {
			// Special nightly channel updating
			$updateData  = $this->checkCoreUpdate(true);
			$buildDate   = $this->getNightlyBuildDate();
			$releaseTime = strtotime($updateData->release_date);
			if ($buildDate) {
				$buildTime = strtotime($buildDate);
				if ($buildTime >= $releaseTime) { //FIXME will ever fail, releaseTime > buildTime (because releaseTime "2014-02-01 14:59:49")
					$this->maniaControl->chat->sendInformation('No new Build available, current build: ' . date("Y-m-d", $buildTime) . '!', $player->login);
					return;
				}
				$this->maniaControl->chat->sendSuccess('New Nightly Build (' . date("Y-m-d", $releaseTime) . ') available, current build: ' . date("Y-m-d", $buildTime) . '!', $player->login);
			} else {
				$this->maniaControl->chat->sendSuccess('New Nightly Build (' . date("Y-m-d", $releaseTime) . ') available!', $player->login);
			}
		}
	}

	/**
	 * Get the Build Date of the local Nightly Build Version
	 *
	 * @return mixed
	 */
	private function getNightlyBuildDate() {
		$nightlyBuildDateFile = ManiaControlDir . '/core/nightly_build.txt';
		if (!file_exists($nightlyBuildDateFile)) {
			return false;
		}
		$fileContent = file_get_contents($nightlyBuildDateFile);
		return $fileContent;
	}

	/**
	 * Handle //coreupdate command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function handle_CoreUpdate(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_UPDATE)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$updateData = $this->checkCoreUpdate(true);
		if (!$updateData) {
			$this->maniaControl->chat->sendError('Update is currently not possible!', $player->login);
			return;
		}

		$this->maniaControl->chat->sendInformation("Starting Update to Version v{$updateData->version}...", $player->login);
		$this->maniaControl->log("Starting Update to Version v{$updateData->version}...");
		$performBackup = $this->maniaControl->settingManager->getSetting($this, self::SETTING_PERFORM_BACKUPS);
		if ($performBackup && !$this->performBackup()) {
			$this->maniaControl->chat->sendError('Creating backup failed.', $player->login);
			$this->maniaControl->log("Creating backup failed.");
		}

		$this->performCoreUpdate($updateData, $player);
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
		/** @var  Plugin $pluginClass */
		$pluginId       = $pluginClass::getId();
		$url            = self::URL_WEBSERVICE . 'plugins?id=' . $pluginId;
		$dataJson       = file_get_contents($url);
		$pluginVersions = json_decode($dataJson);
		if (!$pluginVersions || !isset($pluginVersions[0])) {
			return false;
		}
		$pluginData    = $pluginVersions[0];
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
		$url           = self::URL_WEBSERVICE . 'versions?update=1&current=1&channel=' . $updateChannel;
		$dataJson      = file_get_contents($url);
		$versions      = json_decode($dataJson);
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
		if (!is_dir($backupFolder)) {
			mkdir($backupFolder);
		}
		$backupFileName = $backupFolder . 'backup_' . ManiaControl::VERSION . '_' . date('y-m-d') . '_' . time() . '.zip';
		$backupZip      = new \ZipArchive();
		if ($backupZip->open($backupFileName, \ZipArchive::CREATE) !== TRUE) {
			trigger_error("Couldn't create Backup Zip!");
			return false;
		}
		$excludes   = array('.', '..', 'backup', 'logs', 'ManiaControl.log');
		$pathInfo   = pathInfo(ManiaControlDir);
		$parentPath = $pathInfo['dirname'] . '/';
		$dirName    = $pathInfo['basename'];
		$backupZip->addEmptyDir($dirName);
		$this->zipDirectory($backupZip, ManiaControlDir, strlen($parentPath), $excludes);
		$backupZip->close();
		return true;
	}

	/**
	 * Add a complete Directory to the ZipArchive
	 *
	 * @param \ZipArchive $zipArchive
	 * @param string      $folderName
	 * @param int         $prefixLength
	 * @param array       $excludes
	 * @return bool
	 */
	private function zipDirectory(\ZipArchive &$zipArchive, $folderName, $prefixLength, array $excludes = array()) {
		$folderHandle = opendir($folderName);
		if (!$folderHandle) {
			trigger_error("Couldn't open Folder '{$folderName}' for Backup!");
			return false;
		}
		while(false !== ($file = readdir($folderHandle))) {
			if (in_array($file, $excludes)) {
				continue;
			}
			$filePath  = $folderName . '/' . $file;
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
	 * @param object         $updateData
	 * @param Players\Player $player
	 * @return bool
	 */
	private function performCoreUpdate($updateData = null, Player $player = null) {
		if (!$this->checkPermissions()) {
			if ($player != null) {
				$this->maniaControl->chat->sendError('Update failed: Incorrect Filesystem permissions!', $player->login);
			}
			$this->maniaControl->log('Update failed!');
			return false;
		}

		if (!$updateData) {
			$updateData = $this->checkCoreUpdate();
			if (!$updateData) {
				if ($player != null) {
					$this->maniaControl->chat->sendError('Update failed: No update Data available!', $player->login);
				}
				$this->maniaControl->log('Update failed: No update Data available!');
				return false;
			}
		}

		$this->maniaControl->fileReader->loadFile($updateData->url, function ($updateFileContent, $error) use (&$updateData, &$player) {
			$tempDir = ManiaControlDir . '/temp/';
			if (!is_dir($tempDir)) {
				mkdir($tempDir);
			}
			$updateFileName = $tempDir . basename($updateData->url);


			$bytes = file_put_contents($updateFileName, $updateFileContent);
			if (!$bytes || $bytes <= 0) {
				trigger_error("Couldn't save Update Zip.");
				if ($player != null) {
					$this->maniaControl->chat->sendError('Update failed: Couldn\'t save Update zip!', $player->login);
				}
				return false;
			}
			$zip    = new \ZipArchive();
			$result = $zip->open($updateFileName);
			if ($result !== true) {
				trigger_error("Couldn't open Update Zip. ({$result})");
				if ($player != null) {
					$this->maniaControl->chat->sendError('Update failed: Couldn\'t open Update zip!', $player->login);
				}
				return false;
			}

			$zip->extractTo(ManiaControlDir);
			$zip->close();
			unlink($updateFileName);
			@rmdir($tempDir);

			if ($player != null) {
				$this->maniaControl->chat->sendSuccess('Update finished!', $player->login);
			}
			$this->maniaControl->log("Update finished!");

			$this->maniaControl->restart();

			return true;
		});

		return true;
	}

	/**
	 * Function checks if ManiaControl has sufficient access to files to update them.
	 *
	 * @return bool
	 */
	private function checkPermissions() {
		$writableDirectories = array('core/', 'plugins/');
		$path                = str_replace('core', '', realpath(dirname(__FILE__)));

		foreach($writableDirectories as $writableDirecotry) {
			$files = array();
			$di    = new \RecursiveDirectoryIterator($path . $writableDirecotry);
			foreach(new \RecursiveIteratorIterator($di) as $filename => $file) {
				$files[] = $filename;
			}

			foreach($files as $file) {
				if (substr($file, -1) != '.' && substr($file, -2) != '..') {
					if (!is_writable($file)) {
						$this->maniaControl->log('Cannot update: the file/directory "' . $file . '" is not writable!');
						return false;
					}
				}
			}
		}

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
		if (!in_array($updateChannel, array(self::CHANNEL_RELEASE, self::CHANNEL_BETA, self::CHANNEL_NIGHTLY))) {
			$updateChannel = self::CHANNEL_RELEASE;
		}
		return $updateChannel;
	}
}

<?php

namespace ManiaControl\Update;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Files\FileUtil;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Plugins\PluginMenu;

/**
 * Manager checking for ManiaControl Core and Plugin Updates
 *
 * @author steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
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
	const CHANNEL_RELEASE                = 'release';
	const CHANNEL_BETA                   = 'beta';
	const CHANNEL_NIGHTLY                = 'nightly';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	/** @var UpdateData $coreUpdateData */
	private $coreUpdateData = null;
	private $currentBuildDate = "";

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
		// TODO: 'nightly' only during dev
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_UPDATECHECK_CHANNEL, self::CHANNEL_NIGHTLY);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_PERFORM_BACKUPS, true);

		// Register for callbacks
		$updateInterval = $this->maniaControl->settingManager->getSetting($this, self::SETTING_UPDATECHECK_INTERVAL);
		$this->maniaControl->timerManager->registerTimerListening($this, 'hourlyUpdateCheck', 1000 * 60 * 60 * $updateInterval);
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerJoined');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'autoUpdate');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		//define Permissions
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_UPDATE, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_UPDATECHECK, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		// Register for chat commands
		$this->maniaControl->commandManager->registerCommandListener('checkupdate', $this, 'handle_CheckUpdate', true);
		$this->maniaControl->commandManager->registerCommandListener('coreupdate', $this, 'handle_CoreUpdate', true);
		$this->maniaControl->commandManager->registerCommandListener('pluginupdate', $this, 'handle_PluginUpdate', true);
		$this->maniaControl->commandManager->registerCommandListener('pluginlist', $this, 'handle_PluginList', true);

		$this->currentBuildDate = $this->getNightlyBuildDate();
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

		//Check if a new Core Update is Available
		$self = $this;
		$maniaControl = $this->maniaControl;
		$this->checkCoreUpdateAsync(function (UpdateData $updateData) use ($self, $maniaControl, $time) {
			$buildDate   = strtotime($self->getCurrentBuildDate());
			$releaseTime = strtotime($updateData->releaseDate);
			if ($buildDate < $releaseTime) {
				$updateChannel = $maniaControl->settingManager->getSetting($self, UpdateManager::SETTING_UPDATECHECK_CHANNEL);
				if ($updateChannel != UpdateManager::CHANNEL_NIGHTLY) {
					$maniaControl->log('New ManiaControl Version ' . $updateData->version . ' available!');
				} else {
					$maniaControl->log('New Nightly Build (' . $updateData->releaseDate . ') available!');
				}
				$self->setCoreUpdateData($updateData);
				$self->autoUpdate($time);
			}
		}, true);
	}

	/**
	 * Handle ManiaControl PlayerJoined callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerJoined(Player $player) {
		if (!$this->coreUpdateData) {
			return;
		}
		// Announce available update
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_UPDATE)) {
			return;
		}

		$buildDate   = strtotime($this->currentBuildDate);
		$releaseTime = strtotime($this->coreUpdateData->releaseDate);
		if ($buildDate < $releaseTime) {
			$updateChannel = $this->maniaControl->settingManager->getSetting($this, self::SETTING_UPDATECHECK_CHANNEL);
			if ($updateChannel != self::CHANNEL_NIGHTLY) {
				$this->maniaControl->chat->sendInformation('New ManiaControl Version ' . $this->coreUpdateData->version . ' available!', $player->login);
			} else {
				$this->maniaControl->chat->sendSuccess('New Nightly Build (' . $this->coreUpdateData->releaseDate . ') available!', $player->login);
			}
		}
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

		if (!$this->coreUpdateData) {
			return;
		}

		$version = $this->maniaControl->client->getVersion();
		if($this->coreUpdateData->minDedicatedBuild > $version->build){
			return;
		}

		$buildDate   = strtotime($this->currentBuildDate);
		$releaseTime = strtotime($this->coreUpdateData->releaseDate);
		if ($buildDate && $buildDate >= $releaseTime) {
			return;
		}

		$this->maniaControl->log("Starting Update to Version v{$this->coreUpdateData->version}...");
		$performBackup = $this->maniaControl->settingManager->getSetting($this, self::SETTING_PERFORM_BACKUPS);
		if ($performBackup && !$this->performBackup()) {
			$this->maniaControl->log("Creating Backup failed!");
		}
		$this->performCoreUpdate($this->coreUpdateData);
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
			$this->checkCoreUpdateAsync(function (UpdateData $updateData) use (&$player) {
				if (!$updateData) {
					$this->maniaControl->chat->sendInformation('No Update available!', $player->login);
					return;
				}

				$version = $this->maniaControl->client->getVersion();
				if($updateData->minDedicatedBuild > $version->build){
					$this->maniaControl->chat->sendError("No new Build for this Server-version available!", $player->login);
					return;
				}

				$this->maniaControl->chat->sendSuccess('Update for Version ' . $updateData->version . ' available!', $player->login);
			});
		} else {
			// Special nightly channel updating
			$this->checkCoreUpdateAsync(function (UpdateData $updateData) use (&$player) {
				if (!$updateData) {
					$this->maniaControl->chat->sendInformation('No Update available!', $player->login);
					return;
				}

				$version = $this->maniaControl->client->getVersion();
				if($updateData->minDedicatedBuild > $version->build){
					$this->maniaControl->chat->sendError("No new Build for this Server-version available!", $player->login);
					return;
				}

				$buildTime   = strtotime($this->currentBuildDate);
				$releaseTime = strtotime($updateData->releaseDate);
				if ($buildTime != '') {
					if ($buildTime >= $releaseTime) {
						$this->maniaControl->chat->sendInformation('No new Build available, current build: ' . date("Y-m-d", $buildTime) . '!', $player->login);
						return;
					}
					$this->maniaControl->chat->sendSuccess('New Nightly Build (' . $updateData->releaseDate . ') available, current build: ' . $this->currentBuildDate . '!', $player->login);
				} else {
					$this->maniaControl->chat->sendSuccess('New Nightly Build (' . $updateData->releaseDate . ') available!', $player->login);
				}
			}, true);
		}
	}

	/**
	 * Handle PlayerManialinkPageAnswer callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$update      = (strpos($actionId, PluginMenu::ACTION_PREFIX_UPDATEPLUGIN) === 0);

		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);

		if($update) {
			$pluginClass = substr($actionId, strlen(PluginMenu::ACTION_PREFIX_UPDATEPLUGIN));
			if($pluginClass == 'All') {
				$this->checkPluginsUpdate($player);
			} else {
				$newUpdate = $this->checkPluginUpdate($pluginClass);
				if($newUpdate != false) {
					$newUpdate->pluginClass = $pluginClass;
					$this->updatePlugin($newUpdate, $player, true);
				}
			}
		}
	}

	/**
	 * Get the Build Date of the local Nightly Build Version
	 *
	 * @return String $buildTime
	 */
	private function getNightlyBuildDate() {
		$nightlyBuildDateFile = ManiaControlDir . '/core/nightly_build.txt';
		if (!file_exists($nightlyBuildDateFile)) {
			return '';
		}
		$fileContent = file_get_contents($nightlyBuildDateFile);
		return $fileContent;
	}

	/**
	 * Get the CurrentBuildDate
	 *
	 * @return string
	 */
	public function getCurrentBuildDate() {
		return $this->currentBuildDate;
	}

	/**
	 * Set the Build Date of the local Nightly Build Version
	 *
	 * @param $date
	 * @return mixed
	 */
	private function setNightlyBuildDate($date) {
		$nightlyBuildDateFile   = ManiaControlDir . '/core/nightly_build.txt';
		$success                = file_put_contents($nightlyBuildDateFile, $date);
		$this->currentBuildDate = $date;
		return $success;
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

		$this->checkCoreUpdateAsync(function (UpdateData $updateData) use (&$player) {
			if (!$updateData) {
				$this->maniaControl->chat->sendError('Update is currently not possible!', $player->login);
				return;
			}
			$version = $this->maniaControl->client->getVersion();
			if($updateData->minDedicatedBuild > $version->build){
				$this->maniaControl->chat->sendError("ManiaControl update version requires a newer Dedicated Server version!", $player->login);
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
		}, true);
	}

	/**
	 * Handle //pluginupdate command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function handle_PluginUpdate(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_UPDATE)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$this->checkPluginsUpdate($player);
	}

	/**
	 * Handle //pluginlist command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function handle_PluginList(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_UPDATE)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$url            = ManiaControl::URL_WEBSERVICE . 'plugins';
		$dataJson       = FileUtil::loadFile($url);
		$pluginList     = json_decode($dataJson);
		if (!$pluginList || !isset($pluginList[0])) {
			$this->maniaControl->chat->sendInformation('Pluginlist could not be retrieved from the Web Services!', $player->login);
		}

		$pluginClasses = $this->maniaControl->pluginManager->getPluginClasses();
		$pluginIds = array();
		/** @var  Plugin $class */
		foreach($pluginClasses as $class) {
			$pluginIds[] = $class::getId();
		}

		foreach($pluginList as $plugin) {
			if(!in_array($plugin->id, $pluginIds)) {
				$this->maniaControl->chat->sendInformation($plugin->name.' (v'.$plugin->currentVersion->version.') - '.$plugin->description, $player->login);
			}
		}
	}

	/**
	 * Checks if there are outdated plugins active.
	 * @param Player $player
	 */
	public function checkPluginsUpdate(Player $player = null) {
		$this->maniaControl->log('[UPDATE] Checking plugins for newer versions ...');
		$outdatedPlugins = array();

		foreach ($this->maniaControl->pluginManager->getPluginClasses() as $pluginClass) {
			$pluginData = $this->checkPluginUpdate($pluginClass);
			if ($pluginData != false) {
				$pluginData->pluginClass = $pluginClass;
				$outdatedPlugins[] = $pluginData;
				$this->maniaControl->log('[UPDATE] '.$pluginClass.': There is a newer version available: '.$pluginData->currentVersion->version.'!');
			}
		}

		if (count($outdatedPlugins) > 0) {
			$this->maniaControl->log('[UPDATE] Checking plugins: COMPLETE, there are '.count($outdatedPlugins).' outdated plugins, now updating ...');
			if ($player) {
				$this->maniaControl->chat->sendInformation('Checking plugins: COMPLETE, there are '.count($outdatedPlugins).' outdated plugins, now updating ...', $player->login);
			}
			$this->performPluginsBackup();
			foreach ($outdatedPlugins as $plugin) {
				$this->updatePlugin($plugin, $player);
			}
		} else {
			$this->maniaControl->log('[UPDATE] Checking plugins: COMPLETE, all plugins are up-to-date!');
			if ($player) {
				$this->maniaControl->chat->sendInformation('Checking plugins: COMPLETE, all plugins are up-to-date!', $player->login);
			}
		}
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
		$url            = ManiaControl::URL_WEBSERVICE . 'plugins?id=' . $pluginId;
		$dataJson       = FileUtil::loadFile($url);
		$pluginVersions = json_decode($dataJson);
		if (!$pluginVersions || !isset($pluginVersions[0])) {
			return false;
		}
		$pluginData    = $pluginVersions[0];
		$pluginVersion = $pluginClass::getVersion();
		if ($pluginData->currentVersion->version <= $pluginVersion) {
			return false;
		}
		return $pluginData;
	}

	/**
	 * Check for updates
	 *
	 * @return mixed
	 */
	public function getPluginsUpdates() {
		$pluginUpdates  = array();
		$pluginsWS      = array();

		$url            = ManiaControl::URL_WEBSERVICE . 'plugins';
		$dataJson       = FileUtil::loadFile($url);
		$pluginVersions = json_decode($dataJson);
		if (!$pluginVersions || !isset($pluginVersions[0])) {
			return false;
		}

		foreach($pluginVersions as $plugin) {
			$pluginsWS[$plugin->id] = $plugin;
		}

		/** @var  Plugin $pluginClass */
		foreach($this->maniaControl->pluginManager->getPluginClasses() as $pluginClass) {
			$pluginId = $pluginClass::getId();
			if(array_key_exists($pluginId, $pluginsWS)) {
				if($pluginsWS[$pluginId]->currentVersion->version > $pluginClass::getVersion()) {
					$pluginUpdates[$pluginId] = $pluginsWS[$pluginId];
				}
			}
		}

		if(empty($pluginUpdates)) {
			return false;
		}
		return $pluginUpdates;
	}

	/**
	 * Update pluginfile
	 *
	 * @param        $pluginData
	 * @param Player $player
	 * @param bool   $reopen
	 */
	private function updatePlugin($pluginData, Player $player = null, $reopen = false) {
		$this->maniaControl->fileReader->loadFile($pluginData->currentVersion->url, function ($updateFileContent, $error) use (&$updateData, &$player, &$pluginData, &$reopen) {
			$this->maniaControl->log('[UPDATE] Now updating '.$pluginData->name.' ...');
			if ($player) {
				$this->maniaControl->chat->sendInformation('Now updating '.$pluginData->name.' ...', $player->login);
			}
			$tempDir = ManiaControlDir . '/temp/';
			if (!is_dir($tempDir)) {
				mkdir($tempDir);
			}
			$updateFileName = $tempDir . $pluginData->currentVersion->zipfile;

			$bytes = file_put_contents($updateFileName, $updateFileContent);
			if (!$bytes || $bytes <= 0) {
				trigger_error("Couldn't save plugin Zip.");
				if ($player) {
					$this->maniaControl->chat->sendError('Update failed: Couldn\'t save plugin zip!', $player->login);
				}
				return false;
			}
			$zip    = new \ZipArchive();
			$result = $zip->open($updateFileName);
			if ($result !== true) {
				trigger_error("Couldn't open plugin Zip. ({$result})");
				if ($player) {
					$this->maniaControl->chat->sendError('Update failed: Couldn\'t open plugin zip!', $player->login);
				}
				return false;
			}

			$zip->extractTo(ManiaControlDir.'/plugins');
			$zip->close();
			unlink($updateFileName);
			@rmdir($tempDir);

			$this->maniaControl->log('[UPDATE] Successfully updated '.$pluginData->name.'!');
			if ($player) {
				$this->maniaControl->chat->sendSuccess('Successfully updated '.$pluginData->name.'!', $player->login);
				$this->maniaControl->pluginManager->deactivatePlugin($pluginData->pluginClass);
				$this->maniaControl->pluginManager->activatePlugin($pluginData->pluginClass);

				if ($reopen) {
					$menuId = $this->maniaControl->configurator->getMenuId('Plugins');
					$this->maniaControl->configurator->reopenMenu($player, $menuId);
				}
			}
		});
	}

	/**
	 * Set Core Update Data
	 * 
	 * @param UpdateData $coreUpdateData
	 */
	public function setCoreUpdateData(UpdateData $coreUpdateData = null) {
		$this->coreUpdateData = $coreUpdateData;
	}

	/**
	 * Checks a core update Asynchronously
	 *
	 * @param      $function
	 * @param bool $ignoreVersion
	 */
	private function checkCoreUpdateAsync($function, $ignoreVersion = false) {
		$updateChannel = $this->getCurrentUpdateChannelSetting();
		$url           = ManiaControl::URL_WEBSERVICE . 'versions?update=1&current=1&channel=' . $updateChannel;

		$this->maniaControl->fileReader->loadFile($url, function ($dataJson, $error) use (&$function, $ignoreVersion) {
			$versions = json_decode($dataJson);
			if (!$versions || !isset($versions[0])) {
				return;
			}
			$updateData = new UpdateData($versions[0]);
			if (!$ignoreVersion && $updateData->version <= ManiaControl::VERSION) {
				return;
			}
			
			call_user_func($function, $updateData);
		});
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
	 * Perform a Backup of the plugins
	 *
	 * @return bool
	 */
	private function performPluginsBackup() {
		$backupFolder = ManiaControlDir . '/backup/';
		if (!is_dir($backupFolder)) {
			mkdir($backupFolder);
		}
		$backupFileName = $backupFolder . 'backup_plugins_' . date('y-m-d') . '_' . time() . '.zip';
		$backupZip      = new \ZipArchive();
		if ($backupZip->open($backupFileName, \ZipArchive::CREATE) !== TRUE) {
			trigger_error("Couldn't create Backup Zip!");
			return false;
		}
		$excludes   = array('.', '..');
		$pathInfo   = pathInfo(ManiaControlDir.'/plugins');
		$parentPath = $pathInfo['dirname'] . '/';
		$dirName    = $pathInfo['basename'];
		$backupZip->addEmptyDir($dirName);
		$this->zipDirectory($backupZip, ManiaControlDir.'/plugins', strlen($parentPath), $excludes);
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
	 * @param object $updateData
	 * @param        $player
	 * @return bool
	 */
	private function performCoreUpdate(UpdateData $updateData, Player $player = null) {
		if (!$this->checkPermissions()) {
			if ($player) {
				$this->maniaControl->chat->sendError('Update failed: Incorrect file system permissions!', $player->login);
			}
			$this->maniaControl->log('Update failed!');
			return false;
		}

		if (!isset($updateData->url) && !isset($updateData->releaseDate)) {
			if ($player) {
				$this->maniaControl->chat->sendError('Update failed: No update Data available!', $player->login);
			}
			$this->maniaControl->log('Update failed: No update Data available!');
			return false;
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
				if ($player) {
					$this->maniaControl->chat->sendError('Update failed: Couldn\'t save Update zip!', $player->login);
				}
				return false;
			}
			$zip    = new \ZipArchive();
			$result = $zip->open($updateFileName);
			if ($result !== true) {
				trigger_error("Couldn't open Update Zip. ({$result})");
				if ($player) {
					$this->maniaControl->chat->sendError('Update failed: Couldn\'t open Update zip!', $player->login);
				}
				return false;
			}

			$zip->extractTo(ManiaControlDir);
			$zip->close();
			unlink($updateFileName);
			@rmdir($tempDir);

			//Set the Nightly Build Date
			$this->setNightlyBuildDate($updateData->releaseDate);

			if ($player) {
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
		$writableDirectories = array('/core/', '/plugins/');
		$path                = ManiaControlDir;

		foreach($writableDirectories as $writableDirecotry) {
			$dir = new \RecursiveDirectoryIterator($path . $writableDirecotry);
			foreach(new \RecursiveIteratorIterator($dir) as $filename => $file) {
				if (substr($filename, -1) != '.' && substr($filename, -2) != '..') {
					if (!is_writable($filename)) {
						$this->maniaControl->log('Cannot update: the file/directory "' . $filename . '" is not writable!');
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

<?php

namespace ManiaControl\Update;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Files\BackupUtil;
use ManiaControl\Files\FileUtil;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Manager checking for ManiaControl Core Updates
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
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
	 * Public Properties
	 */
	public $pluginUpdateManager = null;

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	/**
	 * @var UpdateData $coreUpdateData
	 */
	private $coreUpdateData = null;
	private $currentBuildDate = null;

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
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_UPDATECHECK_CHANNEL, self::CHANNEL_BETA);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_PERFORM_BACKUPS, true);

		// Register for callbacks
		$updateInterval = $this->maniaControl->settingManager->getSetting($this, self::SETTING_UPDATECHECK_INTERVAL);
		$this->maniaControl->timerManager->registerTimerListening($this, 'hourlyUpdateCheck', 1000 * 60 * 60 * $updateInterval);
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerJoined');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');

		// define Permissions
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_UPDATE, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_UPDATECHECK, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		// Register for chat commands
		$this->maniaControl->commandManager->registerCommandListener('checkupdate', $this, 'handle_CheckUpdate', true, 'Checks if there is a core update.');
		$this->maniaControl->commandManager->registerCommandListener('coreupdate', $this, 'handle_CoreUpdate', true, 'Performs the core update.');

		// Plugin update manager
		$this->pluginUpdateManager = new PluginUpdateManager($maniaControl);
	}

	/**
	 * Perform Hourly Update Check
	 *
	 * @param $time
	 */
	public function hourlyUpdateCheck($time) {
		$updateCheckEnabled = $this->maniaControl->settingManager->getSetting($this, self::SETTING_ENABLEUPDATECHECK);
		if (!$updateCheckEnabled) {
			$this->setCoreUpdateData();
			return;
		}
		$this->checkUpdate();
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
	 * Start an Update Check
	 */
	public function checkUpdate() {
		$this->checkCoreUpdateAsync(array($this, 'handleUpdateCheck'));
	}

	/**
	 * Checks a Core Update asynchronously
	 *
	 * @param callable $function
	 */
	private function checkCoreUpdateAsync($function) {
		$updateChannel = $this->getCurrentUpdateChannelSetting();
		$url           = ManiaControl::URL_WEBSERVICE . 'versions?current=1&channel=' . $updateChannel;

		$this->maniaControl->fileReader->loadFile($url, function ($dataJson, $error) use (&$function) {
			$versions = json_decode($dataJson);
			if (!$versions || !isset($versions[0])) {
				call_user_func($function, null);
			} else {
				$updateData = new UpdateData($versions[0]);
				call_user_func($function, $updateData);
			}
		});
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

	/**
	 * Handle the fetched Update Data
	 *
	 * @param UpdateData $updateData
	 */
	public function handleUpdateCheck(UpdateData $updateData) {
		if (!$this->checkUpdateData($updateData)) {
			// No new update available
			return;
		}
		if (!$this->checkUpdateDataBuildVersion($updateData)) {
			// Server incompatible
			$this->maniaControl->log("Please update Your Server to '{$updateData->minDedicatedBuild}' in order to receive further Updates!");
			return;
		}

		if ($this->isNightlyUpdateChannel()) {
			$this->maniaControl->log("New Nightly Build ({$updateData->releaseDate}) available!");
		} else {
			$this->maniaControl->log("New ManiaControl Version {$updateData->version} available!");
		}
		$this->setCoreUpdateData($updateData);

		$this->checkAutoUpdate();
	}

	/**
	 * Check if the given Update Data has a new Version and fits for the Server
	 *
	 * @param UpdateData $updateData
	 * @return bool
	 */
	public function checkUpdateData(UpdateData $updateData = null) {
		if (!$updateData || !$updateData->url) {
			// Data corrupted
			return false;
		}

		$isNightly = $this->isNightlyUpdateChannel();
		$buildDate = $this->getNightlyBuildDate();

		if ($isNightly || $buildDate) {
			return $updateData->isNewerThan($buildDate);
		}

		return ($updateData->version > ManiaControl::VERSION);
	}

	/**
	 * Check if ManiaControl is running the Nightly Update Channel
	 *
	 * @param string $updateChannel
	 * @return bool
	 */
	public function isNightlyUpdateChannel($updateChannel = null) {
		if (!$updateChannel) {
			$updateChannel = $this->getCurrentUpdateChannelSetting();
		}
		return ($updateChannel === self::CHANNEL_NIGHTLY);
	}

	/**
	 * Get the Build Date of the local Nightly Build Version
	 *
	 * @return string
	 */
	public function getNightlyBuildDate() {
		if (!$this->currentBuildDate) {
			$nightlyBuildDateFile = ManiaControlDir . '/core/nightly_build.txt';
			if (file_exists($nightlyBuildDateFile)) {
				$this->currentBuildDate = file_get_contents($nightlyBuildDateFile);
			}
		}
		return $this->currentBuildDate;
	}

	/**
	 * Check if the Update Data is compatible with the Server
	 *
	 * @param UpdateData $updateData
	 * @return bool
	 */
	public function checkUpdateDataBuildVersion(UpdateData $updateData = null) {
		if (!$updateData) {
			// Data corrupted
			return false;
		}

		$version = $this->maniaControl->client->getVersion();
		if ($updateData->minDedicatedBuild > $version->build) {
			// Server not compatible
			return false;
		}

		return true;
	}

	/**
	 * Check if an automatic Update should be performed
	 */
	public function checkAutoUpdate() {
		$autoUpdate = $this->maniaControl->settingManager->getSetting($this, self::SETTING_AUTO_UPDATE);
		if (!$autoUpdate) {
			// Auto update turned off
			return;
		}
		if (!$this->coreUpdateData) {
			// No update available
			return;
		}
		if (count($this->maniaControl->playerManager->getPlayers()) > 0) {
			// Server not empty
			return;
		}

		$this->performCoreUpdate();
	}

	/**
	 * Perform a Core Update
	 *
	 * @param Player $player
	 * @return bool
	 */
	private function performCoreUpdate(Player $player = null) {
		if (!$this->coreUpdateData) {
			$message = 'Update failed: No update Data available!';
			if ($player) {
				$this->maniaControl->chat->sendError($message, $player);
			}
			$this->maniaControl->log($message);
			return false;
		}

		$this->maniaControl->log("Starting Update to Version v{$this->coreUpdateData->version}...");

		$directories = array('/core/', '/plugins/');
		if (!FileUtil::checkWritePermissions($directories)) {
			$message = 'Update not possible: Incorrect File System Permissions!';
			if ($player) {
				$this->maniaControl->chat->sendError($message, $player);
			}
			$this->maniaControl->log($message);
			return false;
		}

		$performBackup = $this->maniaControl->settingManager->getSetting($this, self::SETTING_PERFORM_BACKUPS);
		if ($performBackup && !BackupUtil::performFullBackup()) {
			$message = 'Creating Backup before Update failed!';
			if ($player) {
				$this->maniaControl->chat->sendError($message, $player);
			}
			$this->maniaControl->log($message);
		}

		$self = $this;
		$this->maniaControl->fileReader->loadFile($this->coreUpdateData->url, function ($updateFileContent, $error) use (&$self, &$updateData, &$player) {
			if (!$updateFileContent || !$error) {
				$message = "Update failed: Couldn't load Update zip!";
				if ($player) {
					$self->maniaControl->chat->sendError($message, $player);
				}
				logMessage($message);
				return;
			}

			$tempDir        = FileUtil::getTempFolder();
			$updateFileName = $tempDir . basename($updateData->url);

			$bytes = file_put_contents($updateFileName, $updateFileContent);
			if (!$bytes || $bytes <= 0) {
				$message = "Update failed: Couldn't save Update zip!";
				if ($player) {
					$self->maniaControl->chat->sendError($message, $player);
				}
				logMessage($message);
				return;
			}

			$zip    = new \ZipArchive();
			$result = $zip->open($updateFileName);
			if ($result !== true) {
				trigger_error("Couldn't open Update Zip. ({$result})");
				if ($player) {
					$self->maniaControl->chat->sendError("Update failed: Couldn't open Update zip!", $player);
				}
				return;
			}

			$zip->extractTo(ManiaControlDir);
			$zip->close();
			unlink($updateFileName);
			FileUtil::removeTempFolder();

			// Set the Nightly Build Date
			$self->setNightlyBuildDate($updateData->releaseDate);

			$message = 'Update finished!';
			if ($player) {
				$self->maniaControl->chat->sendSuccess($message, $player);
			}
			$self->maniaControl->log($message);

			$self->maniaControl->restart();
		});

		return true;
	}

	/**
	 * Set the Build Date of the local Nightly Build Version
	 *
	 * @param string $date
	 * @return bool
	 */
	private function setNightlyBuildDate($date) {
		$nightlyBuildDateFile   = ManiaControlDir . '/core/nightly_build.txt';
		$success                = (bool)file_put_contents($nightlyBuildDateFile, $date);
		$this->currentBuildDate = $date;
		return $success;
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

		if ($this->isNightlyUpdateChannel()) {
			$this->maniaControl->chat->sendSuccess('New Nightly Build (' . $this->coreUpdateData->releaseDate . ') available!', $player->login);
		} else {
			$this->maniaControl->chat->sendInformation('New ManiaControl Version ' . $this->coreUpdateData->version . ' available!', $player->login);
		}
	}

	/**
	 * Handle Player Disconnect Callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerDisconnect(Player $player) {
		$this->checkAutoUpdate();
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

		$self = $this;
		$this->checkCoreUpdateAsync(function (UpdateData $updateData = null) use (&$self, &$player) {
			if (!$self->checkUpdateData($updateData)) {
				$self->maniaControl->chat->sendInformation('No Update available!', $player->login);
				return;
			}

			if (!$self->checkUpdateDataBuildVersion($updateData)) {
				$self->maniaControl->chat->sendError("Please update Your Server to '{$updateData->minDedicatedBuild}' in order to receive further Updates!", $player->login);
				return;
			}

			$isNightly = $self->isNightlyUpdateChannel();
			if ($isNightly) {
				$buildDate = $self->getNightlyBuildDate();
				if ($buildDate) {
					if ($updateData->isNewerThan($buildDate)) {
						$self->maniaControl->chat->sendInformation("No new Build available! (Current Build: '{$buildDate}')", $player->login);
					} else {
						$self->maniaControl->chat->sendSuccess("New Nightly Build ({$updateData->releaseDate}) available! (Current Build: '{$buildDate}')", $player->login);
					}
				} else {
					$self->maniaControl->chat->sendSuccess("New Nightly Build ('{$updateData->releaseDate}') available!", $player->login);
				}
			} else {
				$self->maniaControl->chat->sendSuccess('Update for Version ' . $updateData->version . ' available!', $player->login);
			}
		});
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

		$self = $this;
		$this->checkCoreUpdateAsync(function (UpdateData $updateData = null) use (&$self, &$player) {
			if (!$updateData) {
				$self->maniaControl->chat->sendError('Update is currently not possible!', $player->login);
				return;
			}
			if (!$self->checkUpdateDataBuildVersion($updateData)) {
				$self->maniaControl->chat->sendError("The Next ManiaControl Update requires a newer Dedicated Server Version!", $player->login);
				return;
			}

			$message = "Starting Update to Version v{$updateData->version}...";
			$self->maniaControl->chat->sendInformation($message, $player->login);
			$self->maniaControl->log($message);

			$performBackup = $self->maniaControl->settingManager->getSetting($self, UpdateManager::SETTING_PERFORM_BACKUPS);
			if ($performBackup && !BackupUtil::performFullBackup()) {
				$message = 'Creating Backup failed!';
				$self->maniaControl->chat->sendError($message, $player->login);
				$self->maniaControl->log($message);
			}

			$self->performCoreUpdate($player);
		});
	}
}

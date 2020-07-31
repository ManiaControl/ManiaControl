<?php

namespace ManiaControl\Update;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Communication\CommunicationAnswer;
use ManiaControl\Communication\CommunicationListener;
use ManiaControl\Communication\CommunicationMethods;
use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\Files\BackupUtil;
use ManiaControl\Files\FileUtil;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Settings\Setting;
use ManiaControl\Settings\SettingManager;

/**
 * Manager checking for ManiaControl Core Updates
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class UpdateManager implements CallbackListener, CommandListener, TimerListener, CommunicationListener {
	/*
	 * Constants
	 */
	const CHANNEL_RELEASE                = 'release';
	const CHANNEL_BETA                   = 'beta';
	const CHANNEL_NIGHTLY                = 'nightly';
	const SETTING_ENABLE_UPDATECHECK     = 'Enable Automatic Core Update Check';
	const SETTING_UPDATECHECK_INTERVAL   = 'Core Update Check Interval (Hours)';
	const SETTING_UPDATECHECK_CHANNEL    = 'Core Update Channel (release, beta, nightly)';
	const SETTING_PERFORM_BACKUPS        = 'Perform Backup before Updating';
	const SETTING_AUTO_UPDATE            = 'Perform update automatically';
	const SETTING_PERMISSION_UPDATE      = 'Update Core';
	const SETTING_PERMISSION_UPDATECHECK = 'Check Core Update';
	const BUILD_DATE_FILE_NAME           = 'build_date.txt';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl     = null;
	private $currentBuildDate = null;
	/** @var UpdateData $coreUpdateData */
	private $coreUpdateData = null;

	/** @var PluginUpdateManager $pluginUpdateManager */
	private $pluginUpdateManager = null;

	/**
	 * Construct a new update manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_ENABLE_UPDATECHECK, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_AUTO_UPDATE, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_UPDATECHECK_INTERVAL, 1);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_UPDATECHECK_CHANNEL, $this->getUpdateChannels());
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_PERFORM_BACKUPS, true);

		// Callbacks
		$updateInterval = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_UPDATECHECK_INTERVAL);
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'hourlyUpdateCheck', 1000 * 60 * 60 * $updateInterval);
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerJoined');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'handleSettingChanged');

		// Permissions
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_UPDATE, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_UPDATECHECK, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		// Chat commands
		$this->maniaControl->getCommandManager()->registerCommandListener('checkupdate', $this, 'handle_CheckUpdate', true, 'Checks if there is a core update.');
		$this->maniaControl->getCommandManager()->registerCommandListener('coreupdate', $this, 'handle_CoreUpdate', true, 'Performs the core update.');

		// Children
		$this->pluginUpdateManager = new PluginUpdateManager($maniaControl);

		// Communication Methods
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::UPDATE_MANIA_CONTROL_CORE, $this, function ($data) {
			$this->checkAndHandleCoreUpdate();
			return new CommunicationAnswer();
		});
	}

	/**
	 * Get the possible update channels
	 *
	 * @return string[]
	 */
	public function getUpdateChannels() {
		// TODO: change default channel on release
		return array(self::CHANNEL_BETA, self::CHANNEL_RELEASE, self::CHANNEL_NIGHTLY);
	}

	/**
	 * Return the plugin update manager
	 *
	 * @return PluginUpdateManager
	 */
	public function getPluginUpdateManager() {
		return $this->pluginUpdateManager;
	}

	/**
	 * Perform Hourly Update Check
	 */
	public function hourlyUpdateCheck() {
		$updateCheckEnabled = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_ENABLE_UPDATECHECK);
		if (!$updateCheckEnabled) {
			$this->setCoreUpdateData();
		} else {
			$this->checkUpdate();
		}
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
	public function checkCoreUpdateAsync($function) {
		$updateChannel = $this->getCurrentUpdateChannelSetting();
		$url           = ManiaControl::URL_WEBSERVICE . 'versions?current=1&channel=' . $updateChannel;

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $url);
		$asyncHttpRequest->setContentType(AsyncHttpRequest::CONTENT_TYPE_JSON);
		$asyncHttpRequest->setCallable(function ($dataJson, $error) use (&$function) {
			if ($error) {
				Logger::logError('Error on UpdateCheck: ' . $error);
				return;
			}

			$versions = json_decode($dataJson);
			if (!$versions || !isset($versions[0])) {
				call_user_func($function);
			} else {
				$updateData = new UpdateData($versions[0]);
				call_user_func($function, $updateData);
			}
		});

		$asyncHttpRequest->getData();
	}

	/**
	 * Retrieve the Update Channel Setting
	 *
	 * @return string
	 */
	public function getCurrentUpdateChannelSetting() {
		$updateChannel = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_UPDATECHECK_CHANNEL);
		$updateChannel = strtolower($updateChannel);
		if (!in_array($updateChannel, $this->getUpdateChannels())) {
			$updateChannel = self::CHANNEL_RELEASE;
		}
		return $updateChannel;
	}

	/**
	 * Handle the fetched Update Data of the hourly Check
	 *
	 * @param UpdateData $updateData
	 */
	public function handleUpdateCheck(UpdateData $updateData = null) {
		if (!$this->checkUpdateData($updateData)) {
			// No new update available
			return;
		}
		if (!$this->checkUpdateDataBuildVersion($updateData)) {
			// Server incompatible
			Logger::logError("Please update Your Server to '{$updateData->minDedicatedBuild}' in order to receive further Updates!");
			return;
		}

		if ($this->coreUpdateData != $updateData) {
			if ($this->isNightlyUpdateChannel()) {
				Logger::log("New Nightly Build ({$updateData->releaseDate}) available!");
			} else {
				Logger::log("New ManiaControl Version {$updateData->version} available!");
			}
			$this->setCoreUpdateData($updateData);
		}

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
		$buildDate = $this->getBuildDate();

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
	 * Get the build date of the local version
	 *
	 * @return string
	 */
	public function getBuildDate() {
		if (!$this->currentBuildDate) {
			$nightlyBuildDateFile = MANIACONTROL_PATH . 'core' . DIRECTORY_SEPARATOR . self::BUILD_DATE_FILE_NAME;
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

		$version = $this->maniaControl->getClient()->getVersion();
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
		$autoUpdate = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_AUTO_UPDATE);
		if (!$autoUpdate) {
			// Auto update turned off
			return;
		}
		if (!$this->coreUpdateData) {
			// No update available
			return;
		}
		if ($this->maniaControl->getPlayerManager()->getPlayerCount(false) > 0) {
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
	public function performCoreUpdate(Player $player = null) {
		if (!$this->coreUpdateData) {
			$message = 'Update failed: No update Data available!';
			if ($player) {
				$this->maniaControl->getChat()->sendError($message, $player);
			}
			Logger::logError($message);
			return false;
		}

		Logger::log("Starting Update to Version v{$this->coreUpdateData->version}...");

		$directories = array('core', 'plugins');
		if (!FileUtil::checkWritePermissions($directories)) {
			$message = 'Update not possible: Incorrect File System Permissions!';
			if ($player) {
				$this->maniaControl->getChat()->sendError($message, $player);
			}
			Logger::logError($message);
			return false;
		}

		$performBackup = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_PERFORM_BACKUPS);
		if ($performBackup && !BackupUtil::performFullBackup()) {
			$message = 'Creating Backup before Update failed!';
			if ($player) {
				$this->maniaControl->getChat()->sendError($message, $player);
			}
			Logger::logError($message);
		}

		$updateData = $this->coreUpdateData;

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $updateData->url);
		$asyncHttpRequest->setCallable(function ($updateFileContent, $error) use (
			$updateData, &$player
		) {
			if (!$updateFileContent || $error) {
				$message = "Update failed: Couldn't load Update zip! {$error}";
				if ($player) {
					$this->maniaControl->getChat()->sendError($message, $player);
				}
				Logger::logError($message);
				return;
			}

			$tempDir = FileUtil::getTempFolder();
			if (!$tempDir) {
				$message = "Update failed: Can't save Update zip!";
				if ($player) {
					$this->maniaControl->getChat()->sendError($message, $player);
				}
				Logger::logError($message);
				return;
			}
			$updateFileName = $tempDir . basename($updateData->url);

			$bytes = file_put_contents($updateFileName, $updateFileContent);
			if (!$bytes || $bytes <= 0) {
				$message = "Update failed: Couldn't save Update zip!";
				if ($player) {
					$this->maniaControl->getChat()->sendError($message, $player);
				}
				Logger::logError($message);
				return;
			}

			$zip    = new \ZipArchive();
			$result = $zip->open($updateFileName);
			if ($result !== true) {
				$message = "Update failed: Couldn't open Update Zip. ({$result})";
				if ($player) {
					$this->maniaControl->getChat()->sendError($message, $player);
				}
				Logger::logError($message);
				unlink($updateFileName);
				return;
			}

			//Don't overwrite the files while testing
			if (!defined('PHP_UNIT_TEST')) {
				$zip->extractTo(MANIACONTROL_PATH);
			}
			$zip->close();

			unlink($updateFileName);
			FileUtil::deleteTempFolder();


			// Set the build date
			$this->setBuildDate($updateData->releaseDate);

			$message = $this->maniaControl->getChat()->formatMessage(
				'Update finished! See what we updated with %s!',
				'//changelog'
			);
			if ($player) {
				$this->maniaControl->getChat()->sendSuccess($message, $player);
			}
			Logger::log($message);

			$this->maniaControl->reboot();
		});

		$asyncHttpRequest->getData();
		return true;
	}

	/**
	 * Set the build date version
	 *
	 * @param string $date
	 * @return bool
	 */
	public function setBuildDate($date) {
		$nightlyBuildDateFile   = MANIACONTROL_PATH . 'core' . DIRECTORY_SEPARATOR . self::BUILD_DATE_FILE_NAME;
		$success                = (bool) file_put_contents($nightlyBuildDateFile, $date);
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
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_UPDATE)) {
			return;
		}

		$message = '';
		if ($this->isNightlyUpdateChannel()) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'New Nightly Build (%s) available!',
				$this->coreUpdateData->releaseDate
			);
		} else {
			$message = $this->maniaControl->getChat()->formatMessage(
				'New ManiaControl Version (%s) available!',
				$this->coreUpdateData->version
			);
		}

		$this->maniaControl->getChat()->sendInformation($message, $player);
	}

	/**
	 * Handle Player Disconnect Callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerDisconnect(Player $player) {
		$this->checkAutoUpdate();
	}

	public function handleSettingChanged(Setting $setting) {
		if (!$setting->setting != self::SETTING_UPDATECHECK_INTERVAL) {
			return;
		}

		$this->maniaControl->getTimerManager()->updateTimerListening($this, 'hourlyUpdateCheck', 1000 * 60 * 60);
	}

	/**
	 * Handle //checkupdate command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function handle_CheckUpdate(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_UPDATECHECK)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$this->checkCoreUpdateAsync(function (UpdateData $updateData = null) use (&$player) {
			if (!$this->checkUpdateData($updateData)) {
				$this->maniaControl->getChat()->sendInformation('No Update available!', $player);
				return;
			}

			if (!$this->checkUpdateDataBuildVersion($updateData)) {
				$message = $this->maniaControl->getChat()->formatMessage(
					'Please update your server to %s in order to receive further ManiaControl updates!',
					$updateData->minDedicatedBuild
				);
				$this->maniaControl->getChat()->sendError($message, $player);
				return;
			}

			$isNightly = $this->isNightlyUpdateChannel();
			if ($isNightly) {
				$buildDate = $this->getBuildDate();
				if ($buildDate) {
					if ($updateData->isNewerThan($buildDate)) {
						$message = $this->maniaControl->getChat()->formatMessage(
							'No new Build available! (Current Build: %s)',
							$buildDate
						);
						$this->maniaControl->getChat()->sendInformation($message, $player);
					} else {
						$message = $this->maniaControl->getChat()->formatMesssage(
							'New Nightly Build (%s) available! (Current Build: %s)',
							$updateData->releaseDate,
							$buildDate
						);
						$this->maniaControl->getChat()->sendSuccess($message, $player);
					}
				} else {
					$message = $this->maniaControl->getChat()->formatMesssage(
						'New Nightly Build (%s) available!',
						$updateData->releaseDate
					);
					$this->maniaControl->getChat()->sendSuccess($message, $player);
				}
			} else {
				$message = $this->maniaControl->getChat()->formatMesssage(
					'Update for Version %s available!',
					$updateData->version
				);
				$this->maniaControl->getChat()->sendSuccess($message, $player);
			}

			$this->coreUpdateData = $updateData;
		});
	}

	/**
	 * Handle //coreupdate command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function handle_CoreUpdate(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_UPDATE)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$this->checkAndHandleCoreUpdate($player);
	}

	/**
	 * Handle CoreUpdate Asnyc
	 *
	 * @param null $player
	 */
	private function checkAndHandleCoreUpdate($player = null) {
		$this->checkCoreUpdateAsync(function (UpdateData $updateData = null) use (&$player) {
			if (!$updateData) {
				if ($player) {
					$this->maniaControl->getChat()->sendError('Update is currently not possible!', $player);
				}
				return;
			}
			if (!$this->checkUpdateDataBuildVersion($updateData)) {
				if ($player) {
					$this->maniaControl->getChat()->sendError('The Next ManiaControl Update requires a newer Dedicated Server Version!', $player);
				}
				return;
			}

			$this->coreUpdateData = $updateData;

			$this->performCoreUpdate($player);
		});
	}
}

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

//register custom zip archive
require_once("CustomZipArchive.php");

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
	/** @var string $coreUpdateData */
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
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_UPDATECHECK_CHANNEL, 'release');
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
	 * Return the plugin update manager
	 *
	 * @return PluginUpdateManager
	 */
	public function getPluginUpdateManager() {
		return $this->pluginUpdateManager;
	}

	/**
	 * Set Core Update Data
	 *
	 * @param string $coreUpdateData
	 */
	public function setCoreUpdateData(string $coreUpdateData = null) {
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
		// ASSUMING LATEST RELEASE ALWAYS
		$url = "https://api.github.com/repos/mmilja/ManiaControl/tags";

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
				$updateVersion = $versions[0]->name;
				$updateVersion = substr($updateVersion, 1);
				call_user_func($function, $updateVersion);
			}
		});

		$asyncHttpRequest->getData();
	}

	/**
	 * Handle the fetched Update Data of the hourly Check
	 *
	 * @param string tag from github repo of the latest release
	 */
	public function handleUpdateCheck(string $updateData = null) {
		if ($this->coreUpdateData != $updateData) {
				Logger::log("New ManiaControl Version {$updateData} available!");
				$this->setCoreUpdateData($updateData);
		}

		$this->checkAutoUpdate();
	}

	/**
	 * Check if the given Update Data has a new Version and fits for the Server
	 *
	 * @param string $updateData
	 * @return bool
	 */
	public function checkUpdateData(string $updateData = null) {
		if (!$updateData) {
			// Data corrupted
			return false;
		}

		return ($updateData != ManiaControl::VERSION);
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

		Logger::log("Starting Update to Version v{$this->coreUpdateData}...");

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
		$updateDataUrl = "https://github.com/mmilja/ManiaControl/archive/refs/tags/v" . $updateData . ".zip";

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $updateDataUrl);
		$asyncHttpRequest->setCallable(function ($updateFileContent, $error) use (
			$updateDataUrl, &$player, $updateData
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
			$updateFileName = $tempDir . basename($updateDataUrl);

			$bytes = file_put_contents($updateFileName, $updateFileContent);
			if (!$bytes || $bytes <= 0) {
				$message = "Update failed: Couldn't save Update zip!";
				if ($player) {
					$this->maniaControl->getChat()->sendError($message, $player);
				}
				Logger::logError($message);
				return;
			}

			$zip    = new \CustomZipArchive();
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
				$zip->extractSubdirTo(MANIACONTROL_PATH, "ManiaControl-$updateData");
			}
			$zip->close();

			unlink($updateFileName);
			FileUtil::deleteTempFolder();

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
			$message = $this->maniaControl->getChat()->formatMessage(
				'New ManiaControl Version (%s) available!',
				$this->coreUpdateData
			);

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

		$this->checkCoreUpdateAsync(function (string $updateData = null) use (&$player) {
			if (!$this->checkUpdateData($updateData)) {
				$this->maniaControl->getChat()->sendInformation('No Update available!', $player);
				return;
			}

			$message = $this->maniaControl->getChat()->formatMessage(
				'Update for Version %s available!',
				$updateData
			);
			$this->maniaControl->getChat()->sendSuccess($message, $player);

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
		$this->checkCoreUpdateAsync(function (string $updateData = null) use (&$player) {
			if (!$updateData) {
				if ($player) {
					$this->maniaControl->getChat()->sendError('Update is currently not possible!', $player);
				}
				return;
			}

			$this->coreUpdateData = $updateData;

			$this->performCoreUpdate($player);
		});
	}
}

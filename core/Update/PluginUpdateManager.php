<?php

namespace ManiaControl\Update;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\Files\BackupUtil;
use ManiaControl\Files\FileUtil;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\InstallMenu;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Plugins\PluginManager;
use ManiaControl\Plugins\PluginMenu;
use ManiaControl\Utils\WebReader;

/**
 * Manager checking for ManiaControl Plugin Updates
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PluginUpdateManager implements CallbackListener, CommandListener, TimerListener {
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Create a new plugin update manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		// Chat commands
		$this->maniaControl->getCommandManager()->registerCommandListener('checkpluginsupdate', $this, 'handle_CheckPluginsUpdate', true, 'Check for Plugin Updates.');
		$this->maniaControl->getCommandManager()->registerCommandListener('pluginsupdate', $this, 'handle_PluginsUpdate', true, 'Perform the Plugin Updates.');
	}

	/**
	 * Handle //checkpluginsupdate command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function handle_CheckPluginsUpdate(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, UpdateManager::SETTING_PERMISSION_UPDATECHECK)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$this->checkPluginsUpdate($player);
	}

	/**
	 * Check if there are Outdated Plugins installed
	 *
	 * @param Player $player
	 */
	public function checkPluginsUpdate(Player $player = null) {
		$message = 'Checking Plugins for newer Versions...';
		if ($player) {
			$this->maniaControl->getChat()->sendInformation($message, $player);
		}
		Logger::log($message);

		$this->maniaControl->getPluginManager()->fetchPluginList(function ($data, $error) use (&$player) {
			if (!$data || $error) {
				$message = 'Error while checking Plugins for newer Versions!';
				if ($player) {
					$this->maniaControl->getChat()->sendError($message, $player);
				}
				Logger::logError($message);
				return;
			}

			$pluginsData   = $this->parsePluginsData($data);
			$pluginClasses = $this->maniaControl->getPluginManager()->getPluginClasses();
			$pluginUpdates = array();

			foreach ($pluginClasses as $pluginClass) {
				/** @var Plugin $pluginClass */
				$pluginId = $pluginClass::getId();
				if (!isset($pluginsData[$pluginId])) {
					continue;
				}
				/** @var PluginUpdateData $pluginData */
				$pluginData    = $pluginsData[$pluginId];
				$pluginVersion = $pluginClass::getVersion();
				if ($pluginData->isNewerThan($pluginVersion)) {
					$pluginUpdates[$pluginId] = $pluginData;
					$message                  = "There is an Update of '{$pluginData->pluginName}' available! ('{$pluginClass}' - Version {$pluginData->version})";
					if ($player) {
						$this->maniaControl->getChat()->sendSuccess($message, $player);
					}
					Logger::log($message);
				}
			}

			if (empty($pluginUpdates)) {
				$message = 'Plugins Update Check completed: All Plugins are up-to-date!';
				if ($player) {
					$this->maniaControl->getChat()->sendSuccess($message, $player);
				}
				Logger::log($message);
			} else {
				$updatesCount = count($pluginUpdates);
				$message      = "Plugins Update Check completed: There are {$updatesCount} Updates available!";
				if ($player) {
					$this->maniaControl->getChat()->sendSuccess($message, $player);
				}
				Logger::log($message);
			}
		});
	}

	/**
	 * Get an Array of Plugin Update Data from the given Web Service Result
	 *
	 * @param mixed $webServiceResult
	 * @return mixed
	 */
	public function parsePluginsData($webServiceResult) {
		if (!$webServiceResult || !is_array($webServiceResult)) {
			return false;
		}
		$pluginsData = array();
		foreach ($webServiceResult as $pluginResult) {
			$pluginData                         = new PluginUpdateData($pluginResult);
			$pluginsData[$pluginData->pluginId] = $pluginData;
		}
		return $pluginsData;
	}

	/**
	 * Handle //pluginsupdate command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function handle_PluginsUpdate(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, UpdateManager::SETTING_PERMISSION_UPDATE)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$this->performPluginsUpdate($player);
	}

	/**
	 * Perform an Update of all outdated Plugins
	 *
	 * @param Player $player
	 */
	public function performPluginsUpdate(Player $player = null) {
		$pluginsUpdates = $this->getPluginsUpdates();
		if (empty($pluginsUpdates)) {
			$message = 'There are no Plugin Updates available!';
			if ($player) {
				$this->maniaControl->getChat()->sendInformation($message, $player);
			}
			Logger::log($message);
			return;
		}

		$message = "Starting Plugins Updating...";
		if ($player) {
			$this->maniaControl->getChat()->sendInformation($message, $player);
		}
		Logger::log($message);

		$performBackup = $this->maniaControl->getSettingManager()->getSettingValue($this->maniaControl->getUpdateManager(), UpdateManager::SETTING_PERFORM_BACKUPS);
		if ($performBackup && !BackupUtil::performPluginsBackup()) {
			$message = 'Creating Backup before Plugins Update failed!';
			if ($player) {
				$this->maniaControl->getChat()->sendError($message, $player);
			}
			Logger::logError($message);
		}

		foreach ($pluginsUpdates as $pluginUpdateData) {
			$this->installPlugin($pluginUpdateData, $player, true);
		}
	}

	/**
	 * Check for Plugin Updates
	 *
	 * @return mixed
	 */
	public function getPluginsUpdates() {
		$url        = ManiaControl::URL_WEBSERVICE . 'plugins';
		$response   = WebReader::getUrl($url);
		$dataJson   = $response->getContent();
		$pluginData = json_decode($dataJson);

		if (!$pluginData || empty($pluginData)) {
			return false;
		}

		$pluginsUpdates = $this->parsePluginsData($pluginData);

		$updates       = array();
		$pluginClasses = $this->maniaControl->getPluginManager()->getPluginClasses();
		foreach ($pluginClasses as $pluginClass) {
			/** @var Plugin $pluginClass */
			$pluginId = $pluginClass::getId();
			if (isset($pluginsUpdates[$pluginId])) {
				/** @var PluginUpdateData $pluginUpdateData */
				$pluginUpdateData = $pluginsUpdates[$pluginId];
				$pluginVersion    = $pluginClass::getVersion();
				if ($pluginUpdateData->isNewerThan($pluginVersion)) {
					$updates[$pluginId] = $pluginUpdateData;
				}
			}
		}

		if (empty($updates)) {
			return false;
		}
		return $updates;
	}

	/**
	 * Load the given Plugin Update Data
	 *
	 * @param PluginUpdateData $pluginUpdateData
	 * @param Player           $player
	 * @param bool             $update
	 */
	private function installPlugin(PluginUpdateData $pluginUpdateData, Player $player = null, $update = false) {
		if ($player && !$this->maniaControl->getAuthenticationManager()->checkPermission($player, InstallMenu::SETTING_PERMISSION_INSTALL_PLUGINS))
		{
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		
		if (ManiaControl::VERSION < $pluginUpdateData->minManiaControlVersion) {
			$message = "Your ManiaControl Version v" . ManiaControl::VERSION . " is too old for this Plugin (min Required Version): ' . {$pluginUpdateData->minManiaControlVersion}!";
			if ($player) {
				$this->maniaControl->getChat()->sendError($message, $player);
			}
			Logger::logError($message);
			return;
		}

		if ($pluginUpdateData->maxManiaControlVersion != -1 && ManiaControl::VERSION > $pluginUpdateData->maxManiaControlVersion) {
			$message = "Your ManiaControl Version v" . ManiaControl::VERSION . " is too new for this Plugin (max Version of the Plugin: ' . {$pluginUpdateData->maxManiaControlVersion}!";
			if ($player) {
				$this->maniaControl->getChat()->sendError($message, $player);
			}
			Logger::logError($message);
			return;
		}

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $pluginUpdateData->url);
		$asyncHttpRequest->setCallable(function ($updateFileContent, $error) use (
			&$pluginUpdateData, &$player, &$update
		) {
			if (!$updateFileContent || $error) {
				$message = $this->maniaControl->getChat()->formatMessage(
					"Error loading Update Data for %s: {$error}!",
					$pluginUpdateData->pluginName
				);
				if ($player) {
					$this->maniaControl->getChat()->sendError($message, $player);
				}
				Logger::logError($message);
				return;
			}

			$actionNoun     = ($update ? 'Update' : 'Install');
			$actionVerb     = ($update ? 'Updating' : 'Installing');
			$actionVerbDone = ($update ? 'updated' : 'installed');

			$message = $this->maniaControl->getChat()->formatMessage(
				"Now {$actionVerb} %s ...",
				$pluginUpdateData->pluginName
			);
			if ($player) {
				$this->maniaControl->getChat()->sendInformation($message, $player);
			}
			Logger::log($message);

			$tempDir        = FileUtil::getTempFolder();
			$updateFileName = $tempDir . $pluginUpdateData->zipfile;

			$bytes = @file_put_contents($updateFileName, $updateFileContent);
			if (!$bytes || $bytes <= 0) {
				$message = "Plugin {$actionNoun} failed: Couldn't save {$actionNoun} Zip!";
				if ($player) {
					$this->maniaControl->getChat()->sendError($message, $player);
				}
				Logger::logError($message);
				return;
			}

			$zip    = new \ZipArchive();
			$result = $zip->open($updateFileName);
			if ($result !== true) {
				$message = "Plugin {$actionNoun} failed: Couldn't open {$actionNoun} Zip! ({$result})";
				if ($player) {
					$this->maniaControl->getChat()->sendError($message, $player);
				}
				Logger::logError($message);
				return;
			}

			$zip->extractTo(MANIACONTROL_PATH . 'plugins' . DIRECTORY_SEPARATOR);
			$zip->close();
			@unlink($updateFileName);
			FileUtil::deleteTempFolder();

			$messageExtra = '';
			if ($update) {
				$messageExtra = ' (Restart ManiaControl to load the new Version!)';
			}
			$message = $this->maniaControl->getChat()->formatMessage(
				"Successfully {$actionVerbDone} %s!{$messageExtra}",
				$pluginUpdateData->pluginName
			);
			if ($player) {
				$this->maniaControl->getChat()->sendSuccess($message, $player);
			}
			Logger::log($message);

			if (!$update) {
				$newPluginClasses = $this->maniaControl->getPluginManager()->loadPlugins();

				if (empty($newPluginClasses)) {
					$message = $this->maniaControl->getChat()->formatMessage(
						"Loading fresh installed Plugin %s failed, try to restart ManiaControl!",
						$pluginUpdateData->pluginName
					);
					if ($player) {
						$this->maniaControl->getChat()->sendError($message, $player);
					}
					Logger::log($message);
				} else {
					$message = $this->maniaControl->getChat()->formatMessage(
						"Successfully loaded fresh installed Plugin %s!",
						$pluginUpdateData->pluginName
					);
					if ($player) {
						$this->maniaControl->getChat()->sendSuccess($message, $player);
					}
					Logger::log($message);

					$this->maniaControl->getConfigurator()->showMenu($player, $this->maniaControl->getPluginManager()->getPluginInstallMenu());
				}
			}
		});

		$asyncHttpRequest->getData();
	}

	/**
	 * Handle PlayerManialinkPageAnswer callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];
		$update   = (strpos($actionId, PluginMenu::ACTION_PREFIX_UPDATEPLUGIN) === 0);
		$install  = (strpos($actionId, InstallMenu::ACTION_PREFIX_INSTALL_PLUGIN) === 0);
		if (!$update && !$install) {
			return;
		}

		$login  = $callback[1][1];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);

		if ($update) {
			$pluginClass = substr($actionId, strlen(PluginMenu::ACTION_PREFIX_UPDATEPLUGIN));
			if ($pluginClass === 'All') {
				$this->performPluginsUpdate($player);
			} else {
				$pluginUpdateData = $this->getPluginUpdate($pluginClass);
				if ($pluginUpdateData) {
					$this->installPlugin($pluginUpdateData, $player, true);
				} else {
					$message = 'Error loading Plugin Update Data!';
					$this->maniaControl->getChat()->sendError($message, $player);
				}
			}
		} else {
			$pluginId = substr($actionId, strlen(InstallMenu::ACTION_PREFIX_INSTALL_PLUGIN));

			$url = ManiaControl::URL_WEBSERVICE . 'plugins/' . $pluginId;

			$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $url);
			$asyncHttpRequest->setContentType(AsyncHttpRequest::CONTENT_TYPE_JSON);
			$asyncHttpRequest->setCallable(function ($data, $error) use (&$player) {
				if ($error || !$data) {
					$message = "Error loading Plugin Install Data! {$error}";
					$this->maniaControl->getChat()->sendError($message, $player);
					return;
				}

				$data = json_decode($data);
				if (!$data) {
					$message = "Error loading Plugin Install Data! {$error}";
					$this->maniaControl->getChat()->sendError($message, $player);
					return;
				}

				$pluginUpdateData = new PluginUpdateData($data);
				$this->installPlugin($pluginUpdateData, $player);
			});

			$asyncHttpRequest->getData();
		}
	}

	/**
	 * Check given Plugin Class for Update
	 *
	 * @param string $pluginClass
	 * @param bool   $skipPluginClassFetch
	 * @return mixed
	 **/
	public static function getPluginUpdate($pluginClass, $skipPluginClassFetch = false) {
		if (!$skipPluginClassFetch) {
			//Used to avoid recursion in the isPluginClass Method
			$pluginClass = PluginManager::getPluginClass($pluginClass);
		}

		/** @var Plugin $pluginClass */
		$pluginId      = $pluginClass::getId();
		$url           = ManiaControl::URL_WEBSERVICE . 'plugins/' . $pluginId;
		$response      = WebReader::getUrl($url);
		$dataJson      = $response->getContent();
		$pluginVersion = json_decode($dataJson);
		if (!$pluginVersion || !property_exists($pluginVersion, 'id')) {
			return false;
		}

		$pluginUpdateData = new PluginUpdateData($pluginVersion);
		$version          = $pluginClass::getVersion();

		if ($pluginUpdateData->isNewerThan($version) && ($pluginUpdateData->maxManiaControlVersion == -1 || $pluginUpdateData->maxManiaControlVersion >= ManiaControl::VERSION)) {
			return $pluginUpdateData;
		}
		return false;
	}
}

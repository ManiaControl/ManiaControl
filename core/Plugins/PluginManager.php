<?php

namespace ManiaControl\Plugins;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\EchoListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Communication\CommunicationListener;
use ManiaControl\Files\AsyncHttpRequest;
use ManiaControl\Files\FileUtil;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Manialinks\SidebarMenuEntryListener;
use ManiaControl\Update\PluginUpdateManager;
use ManiaControl\Utils\ClassUtil;
use ReflectionClass;

/**
 * Class managing Plugins
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PluginManager {
	/*
	 * Constants
	 */
	const TABLE_PLUGINS      = 'mc_plugins';
	const CB_PLUGIN_LOADED   = 'PluginManager.PluginLoaded';
	const CB_PLUGIN_UNLOADED = 'PluginManager.PluginUnloaded';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var PluginMenu $pluginMenu */
	private $pluginMenu = null;
	/** @var InstallMenu $pluginInstallMenu */
	private $pluginInstallMenu = null;
	/** @var Plugin[] $activePlugins */
	private $activePlugins = array();
	/** @var string[] $pluginClasses */
	private $pluginClasses = array();

	/**
	 * Construct a new plugin manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		$this->pluginMenu = new PluginMenu($maniaControl);
		$this->maniaControl->getConfigurator()->addMenu($this->pluginMenu);

		$this->pluginInstallMenu = new InstallMenu($maniaControl);
		$this->maniaControl->getConfigurator()->addMenu($this->pluginInstallMenu);
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli            = $this->maniaControl->getDatabase()->getMysqli();
		$pluginsTableQuery = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_PLUGINS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`className` varchar(100) NOT NULL,
				`active` tinyint(1) NOT NULL DEFAULT '0',
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `className` (`className`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ManiaControl plugin status' AUTO_INCREMENT=1;";
		$tableStatement    = $mysqli->prepare($pluginsTableQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$tableStatement->execute();
		if ($tableStatement->error) {
			trigger_error($tableStatement->error, E_USER_ERROR);
			return false;
		}
		$tableStatement->close();
		return true;
	}

	/**
	 * Get the Plugin Id if the given Class is a Plugin
	 *
	 * @api
	 * @param string $pluginClass
	 * @return int
	 */
	public static function getPluginId($pluginClass) {
		if (self::isPluginClass($pluginClass)) {
			/** @var Plugin $pluginClass */
			return $pluginClass::getId();
		}
		return null;
	}

	/**
	 * Get the Plugin version if a given Class is a Plugin
	 *
	 * @api
	 * @param $pluginClass
	 * @return null|int
	 */
	public static function getPluginVersion($pluginClass) {
		if (self::isPluginClass($pluginClass)) {
			/** @var Plugin $pluginClass */
			return $pluginClass::getVersion();
		}
		return null;
	}


	/**
	 * Check if the given class implements the plugin interface
	 *
	 * @api
	 * @param string $pluginClass
	 * @return bool
	 */
	public static function isPluginClass($pluginClass) {
		$pluginClass = ClassUtil::getClass($pluginClass);
		if (!class_exists($pluginClass, false)) {
			return false;
		}
		$interfaces = class_implements($pluginClass, false);
		if (!$interfaces) {
			return false;
		}
		if (!in_array(Plugin::PLUGIN_INTERFACE, $interfaces)) {
			return false;
		}

		$reflector      = new ReflectionClass($pluginClass);
		$className      = $pluginClass;
		$splitNameSpace = explode('\\', $pluginClass);
		if (is_array($splitNameSpace)) {
			$className = end($splitNameSpace);
		}
		if (FileUtil::getFileName($reflector->getFileName()) != $className) {
			$updateAvailable = PluginUpdateManager::getPluginUpdate($pluginClass, true);
			Logger::logError("FileName does not match Plugin ClassName; Plugin: " . $className);

			if ($updateAvailable) {
				//Update Is available, plugin will be deleted
				unlink($reflector->getFileName());
				$message = "There is a new version of " . $className . " available, restart ManiaControl and install the new version after from the Install Plugins Menu!";
				Logger::log($message);
				Logger::log("File " . $className . " will be deleted!");
				//TODO maybe a better solution, throw exception here and do stuff automatically
			}
			return false;
		}

		return true;
	}

	/**
	 * Deactivate the Plugin with the given Class
	 *
	 * @param string $pluginClass
	 * @return bool
	 */
	public function deactivatePlugin($pluginClass) {
		$pluginClass = $this->getPluginClass($pluginClass);
		if (!$pluginClass) {
			return false;
		}

		if (!$this->isPluginActive($pluginClass)) {
			//If Error Occured while loading Plugin
			$this->savePluginStatus($pluginClass, false);
			return false;
		}

		/** @var Plugin $plugin */
		$plugin = $this->activePlugins[$pluginClass];
		unset($this->activePlugins[$pluginClass]);

		if ($plugin) {
			$this->unloadPlugin($plugin);
		}

		$this->savePluginStatus($pluginClass, false);

		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_PLUGIN_UNLOADED, $pluginClass, $plugin);

		return true;
	}

	/**
	 * Unload the Plugin and all its Features
	 *
	 * @param \ManiaControl\Plugins\Plugin $plugin
	 */
	private function unloadPlugin(Plugin $plugin) {
		$plugin->unload();

		if ($plugin instanceof EchoListener) {
			$this->maniaControl->getEchoManager()->unregisterEchoListener($plugin);
		}
		if ($plugin instanceof CallbackListener) {
			$this->maniaControl->getCallbackManager()->unregisterCallbackListener($plugin);
			$this->maniaControl->getCallbackManager()->unregisterScriptCallbackListener($plugin);
		}
		if ($plugin instanceof CommandListener) {
			$this->maniaControl->getCommandManager()->unregisterCommandListener($plugin);
		}
		if ($plugin instanceof ManialinkPageAnswerListener) {
			$this->maniaControl->getManialinkManager()->unregisterManialinkPageAnswerListener($plugin);
		}
		if ($plugin instanceof TimerListener) {
			$this->maniaControl->getTimerManager()->unregisterTimerListenings($plugin);
		}
		if ($plugin instanceof SidebarMenuEntryListener) {
			$this->maniaControl->getManialinkManager()->getSidebarMenuManager()->deleteMenuEntries($plugin);
		}
		if ($plugin instanceof CommunicationListener) {
			$this->maniaControl->getCommunicationManager()->unregisterCommunicationListener($plugin);
		}
	}

	/**
	 * Get the Class of the Plugin
	 *
	 * @api
	 * @param mixed $pluginClass
	 * @return string
	 */
	public static function getPluginClass($pluginClass) {
		$pluginClass = ClassUtil::getClass($pluginClass);
		if (!self::isPluginClass($pluginClass)) {
			return null;
		}
		return $pluginClass;
	}

	/**
	 * Check if the Plugin is currently running
	 *
	 * @api
	 * @param string $pluginClass
	 * @return bool
	 */
	public function isPluginActive($pluginClass) {
		$pluginClass = $this->getPluginClass($pluginClass);
		return isset($this->activePlugins[$pluginClass]);
	}

	/**
	 * Save Plugin Status in Database
	 *
	 * @param string $className
	 * @param bool   $active
	 * @return bool
	 */
	private function savePluginStatus($className, $active) {
		$mysqli            = $this->maniaControl->getDatabase()->getMysqli();
		$pluginStatusQuery = "INSERT INTO `" . self::TABLE_PLUGINS . "` (
				`className`,
				`active`
				) VALUES (
				?, ?
				) ON DUPLICATE KEY UPDATE
				`active` = VALUES(`active`);";
		$pluginStatement   = $mysqli->prepare($pluginStatusQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$activeInt = ($active ? 1 : 0);
		$pluginStatement->bind_param('si', $className, $activeInt);
		$pluginStatement->execute();
		if ($pluginStatement->error) {
			trigger_error($pluginStatement->error);
			$pluginStatement->close();
			return false;
		}
		$pluginStatement->close();

		return true;
	}

	/**
	 * Load complete Plugins Directory and start all configured Plugins
	 *
	 * @return string[]
	 */
	public function loadPlugins() {
		$pluginsDirectory = MANIACONTROL_PATH . 'plugins' . DIRECTORY_SEPARATOR;

		$classesBefore = get_declared_classes();
		$this->loadPluginFiles($pluginsDirectory);
		$classesAfter = get_declared_classes();

		$newPluginClasses = array();

		$newClasses = array_diff($classesAfter, $classesBefore);
		foreach ($newClasses as $className) {
			if (!self::isPluginClass($className)) {
				continue;
			}
			if (!self::validatePluginClass($className)) {
				$message = "The plugin class '{$className}' isn't correctly implemented: You need to return a proper ID by registering it on maniacontrol.com!";
				Logger::logWarning($message);
				if (!DEV_MODE) {
					$message = 'Fix the plugin or turn on DEV_MODE!';
					$this->maniaControl->quit($message, true);
				}
			}

			if (!$this->addPluginClass($className)) {
				continue;
			}
			array_push($newPluginClasses, $className);

			/** @var Plugin $className */
			$className::prepare($this->maniaControl);

			if ($this->getSavedPluginStatus($className)) {
				$this->activatePlugin($className);
			}
		}

		return $newPluginClasses;
	}

	/**
	 * Load all Plugin Files from the Directory
	 *
	 * @param string $directory
	 */
	public function loadPluginFiles($directory = '') {
		if (!is_readable($directory) || !is_dir($directory)) {
			return;
		}
		$pluginFiles = scandir($directory);
		foreach ($pluginFiles as $pluginFile) {
			if (FileUtil::isHiddenFile($pluginFile)) {
				continue;
			}

			$filePath = $directory . $pluginFile;
			if (is_file($filePath)) {
				if (!FileUtil::isPhpFileName($pluginFile)) {
					continue;
				}
				$success = include_once $filePath;
				if (!$success) {
					Logger::logError("Couldn't load file '{$filePath}'!");
				}
				continue;
			}

			$dirPath = $directory . $pluginFile;
			if (is_dir($dirPath)) {
				$this->loadPluginFiles($dirPath . DIRECTORY_SEPARATOR);
				continue;
			}
		}
	}

	/**
	 * Validate that the given class is a correctly implemented plugin class
	 *
	 * @param string $pluginClass
	 * @return bool
	 */
	private static function validatePluginClass($pluginClass) {
		if (!self::isPluginClass($pluginClass)) {
			return false;
		}
		/** @var Plugin $pluginClass */
		return ($pluginClass::getId() > 0);
	}

	/**
	 * Add the class to array of loaded plugin classes
	 *
	 * @param string $pluginClass
	 * @return bool
	 */
	public function addPluginClass($pluginClass) {
		$pluginClass = $this->getPluginClass($pluginClass);
		if (in_array($pluginClass, $this->pluginClasses)) {
			return false;
		}
		if (!$this->isPluginClass($pluginClass)) {
			return false;
		}
		array_push($this->pluginClasses, $pluginClass);
		sort($this->pluginClasses);
		return true;
	}

	/**
	 * Get plugin status from database
	 *
	 * @param string $className
	 * @return bool
	 */
	public function getSavedPluginStatus($className) {
		$mysqli            = $this->maniaControl->getDatabase()->getMysqli();
		$pluginStatusQuery = "SELECT `active` FROM `" . self::TABLE_PLUGINS . "`
				WHERE `className` = ?;";
		$pluginStatement   = $mysqli->prepare($pluginStatusQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$pluginStatement->bind_param('s', $className);
		$pluginStatement->execute();
		if ($pluginStatement->error) {
			trigger_error($pluginStatement->error);
			$pluginStatement->close();
			return false;
		}
		$pluginStatement->store_result();
		if ($pluginStatement->num_rows <= 0) {
			$pluginStatement->free_result();
			$pluginStatement->close();
			$this->savePluginStatus($className, false);
			return false;
		}
		$pluginStatement->bind_result($activeInt);
		$pluginStatement->fetch();
		$active = ($activeInt === 1);
		$pluginStatement->free_result();
		$pluginStatement->close();
		return $active;
	}

	/**
	 * Activate and start the plugin with the given name
	 *
	 * @param string $pluginClass
	 * @param string $adminLogin
	 * @return bool
	 */
	public function activatePlugin($pluginClass, $adminLogin = null) {
		if (!$this->isPluginClass($pluginClass)) {
			return false;
		}
		if ($this->isPluginActive($pluginClass)) {
			return false;
		}

		/** @var Plugin $plugin */
		$plugin = new $pluginClass();

		try {
			$plugin->load($this->maniaControl);
		} catch (\Exception $e) {
			//Unload the Plugin and all its features
			$this->unloadPlugin($plugin);

			$this->maniaControl->getChat()->sendException($e, $adminLogin);
			$message = $this->maniaControl->getChat()->formatMessage(
				'Error during Plugin Activation of %s!',
				$pluginClass
			);
			$this->maniaControl->getChat()->sendError($message, $adminLogin);
			Logger::logError($message);
			$this->savePluginStatus($pluginClass, false);
			return false;
		}

		$this->activePlugins[$pluginClass] = $plugin;
		$this->savePluginStatus($pluginClass, true);

		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_PLUGIN_LOADED, $pluginClass, $plugin);

		return true;
	}

	/**
	 * Check if the Plugin with the given ID is already installed and loaded
	 *
	 * @param int $pluginId
	 * @return bool
	 */
	public function isPluginIdInstalled($pluginId) {
		foreach ($this->pluginClasses as $pluginClass) {
			/** @var Plugin $pluginClass */
			if ($pluginClass::getId() == $pluginId) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns a Plugin if it is activated
	 *
	 * @param string $pluginClass
	 * @return Plugin
	 */
	public function getPlugin($pluginClass) {
		if ($this->isPluginActive($pluginClass)) {
			return $this->activePlugins[$pluginClass];
		}
		return null;
	}

	/**
	 * Get all declared plugin class names
	 *
	 * @return string[]
	 */
	public function getPluginClasses() {
		return $this->pluginClasses;
	}

	/**
	 * Get the Ids of all active Plugins
	 *
	 * @return string[]
	 */
	public function getActivePluginsIds() {
		$pluginsIds = array();
		foreach ($this->getActivePlugins() as $plugin) {
			$pluginId = $plugin::getId();
			if (is_numeric($pluginId)) {
				array_push($pluginsIds, $pluginId);
			}
		}
		return $pluginsIds;
	}

	/**
	 * Get all active Plugins
	 *
	 * @return Plugin[]
	 */
	public function getActivePlugins() {
		return $this->activePlugins;
	}

	/**
	 * Fetch the Plugins List from the ManiaControl Website
	 *
	 * @param callable $function
	 */
	public function fetchPluginList(callable $function) {
		$url = ManiaControl::URL_WEBSERVICE . 'plugins';

		$asyncHttpRequest = new AsyncHttpRequest($this->maniaControl, $url);
		$asyncHttpRequest->setContentType(AsyncHttpRequest::CONTENT_TYPE_JSON);
		$asyncHttpRequest->setCallable(function ($dataJson, $error) use (&$function) {
			$data = json_decode($dataJson);
			call_user_func($function, $data, $error);
		});

		$asyncHttpRequest->getData();
	}

	/**
	 * @internal
	 * @return \ManiaControl\Plugins\PluginMenu
	 */
	public function getPluginMenu() {
		return $this->pluginMenu;
	}

	/**
	 * @internal
	 * @return \ManiaControl\Plugins\InstallMenu
	 */
	public function getPluginInstallMenu() {
		return $this->pluginInstallMenu;
	}
}

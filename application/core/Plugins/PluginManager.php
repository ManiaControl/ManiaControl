<?php

namespace ManiaControl\Plugins;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;

/**
 * Class managing Plugins
 *
 * @author steeffeen & kremsy
 */
class PluginManager {
	/**
	 * Constants
	 */
	const TABLE_PLUGINS = 'mc_plugins';

	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $pluginMenu = null;
	private $activePlugins = array();
	private $pluginClasses = array();

	/**
	 * Construct plugin manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		$this->pluginMenu = new PluginMenu($maniaControl);
		$this->maniaControl->configurator->addMenu($this->pluginMenu);
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli            = $this->maniaControl->database->mysqli;
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
	 * Check if the plugin is running
	 *
	 * @param string $pluginClass
	 * @return bool
	 */
	public function isPluginActive($pluginClass) {
		if (is_object($pluginClass)) {
			$pluginClass = get_class($pluginClass);
		}
		return isset($this->activePlugins[$pluginClass]);
	}

	/**
	 * Check if the given class implements the plugin interface
	 *
	 * @param string $pluginClass
	 * @return bool
	 */
	public function isPluginClass($pluginClass) {
		if (is_object($pluginClass)) {
			$pluginClass = get_class($pluginClass);
		}
		if (!in_array(Plugin::PLUGIN_INTERFACE, class_implements($pluginClass))) {
			return false;
		}
		return true;
	}

	/**
	 * Add the class to array of loaded plugin classes
	 *
	 * @param string $pluginClass
	 * @return bool
	 */
	public function addPluginClass($pluginClass) {
		if (is_object($pluginClass)) {
			$pluginClass = get_class($pluginClass);
		}
		if (in_array($pluginClass, $this->pluginClasses)) {
			return false;
		}
		if (!$this->isPluginClass($pluginClass)) {
			return false;
		}
		array_push($this->pluginClasses, $pluginClass);
		return true;
	}

	/**
	 * Activate and start the plugin with the given name
	 *
	 * @param string $pluginClass
	 * @param string $adminLogin
	 * @return bool
	 */
	public function activatePlugin($pluginClass, $adminLogin = null) {
		if (!is_string($pluginClass)) {
			return false;
		}
		if (!$this->isPluginClass($pluginClass)) {
			return false;
		}
		if ($this->isPluginActive($pluginClass)) {
			return false;
		}
		$plugin = new $pluginClass();
		/** @var Plugin $plugin */
		$this->activePlugins[$pluginClass] = $plugin;
		$this->savePluginStatus($pluginClass, true);
		try {
			$plugin->load($this->maniaControl);
		} catch(\Exception $e) {
			$this->maniaControl->chat->sendError('Error while plugin activating ' . $pluginClass . ': ' . $e->getMessage(), $adminLogin);
			$this->maniaControl->log('Error while plugin activation: ' . $pluginClass . ': ' . $e->getMessage());
			unset($this->activePlugins[$pluginClass]);
			$this->savePluginStatus($pluginClass, false);
			return false;
		}

		$this->savePluginStatus($pluginClass, true);
		return true;
	}

	/**
	 * Deactivate the plugin with the given class
	 *
	 * @param string $pluginClass
	 * @return bool
	 */
	public function deactivatePlugin($pluginClass) {
		if (is_object($pluginClass)) {
			$pluginClass = get_class($pluginClass);
		}
		if (!$this->isPluginActive($pluginClass)) {
			return false;
		}
		$plugin = $this->activePlugins[$pluginClass];
		/** @var Plugin $plugin */
		unset($this->activePlugins[$pluginClass]);
		$plugin->unload();
		$interfaces = class_implements($pluginClass);
		if (in_array(CallbackListener::CALLBACKLISTENER_INTERFACE, $interfaces)) {
			$this->maniaControl->callbackManager->unregisterCallbackListener($plugin);
			$this->maniaControl->callbackManager->unregisterScriptCallbackListener($plugin);
		}
		if (in_array(ManialinkPageAnswerListener::MANIALINKPAGEANSWERLISTENER_INTERFACE, $interfaces)) {
			$this->maniaControl->manialinkManager->unregisterManialinkPageAnswerListener($plugin);
		}
		$this->savePluginStatus($pluginClass, false);
		return true;
	}


	/**
	 * Load complete plugins directory and start all configured plugins
	 */
	public function loadPlugins($dir = '') {
		//TODO first include all files, than handle plugin activation
		$pluginsDirectory = ManiaControlDir . '/plugins/' . $dir . '/';
		$pluginFiles      = scandir($pluginsDirectory, 0);
		foreach($pluginFiles as $pluginFile) {
			if (stripos($pluginFile, '.') === 0) {
				continue;
			}

			if (is_dir($pluginsDirectory . $pluginFile)) {
				$this->loadPlugins($pluginFile);
				continue;
			}

			$classesBefore = get_declared_classes();
			$success       = include_once $pluginsDirectory . $pluginFile;
			if (!$success) {
				continue;
			}
			$classesAfter = get_declared_classes();
			$newClasses   = array_diff($classesAfter, $classesBefore);
			foreach($newClasses as $className) {
				if (!$this->isPluginClass($className)) {
					continue;
				}

				//Prepare Plugin
				$className::prepare($this->maniaControl);

				$this->addPluginClass($className);
				if ($this->isPluginActive($className)) {
					continue;
				}
				if (!$this->getSavedPluginStatus($className)) {
					continue;
				}
				$this->activatePlugin($className);
			}
		}
	}

	/**
	 * Returns an Plugin if it is activated
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
	 * @return array
	 */
	public function getPluginClasses() {
		return $this->pluginClasses;
	}

	/**
	 * Get all active plugins
	 *
	 * @return array
	 */
	public function getActivePlugins() {
		return $this->activePlugins;
	}

	/**
	 * Save plugin status in database
	 *
	 * @param string $className
	 * @param bool   $active
	 * @return bool
	 */
	private function savePluginStatus($className, $active) {
		$mysqli            = $this->maniaControl->database->mysqli;
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
	 * Get plugin status from database
	 *
	 * @param string $className
	 * @return bool
	 */
	private function getSavedPluginStatus($className) {
		$mysqli            = $this->maniaControl->database->mysqli;
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
}

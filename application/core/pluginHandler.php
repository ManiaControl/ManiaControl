<?php

namespace ManiaControl;

/**
 * Class handling plugins
 *
 * @author Lukas Kremsmayr and steeffeen
 */
class PluginHandler {
	/**
	 * Constants
	 */
	const TABLE_PLUGINS = 'mc_plugins';
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $activePlugins = array();
	private $pluginClasses = array();

	/**
	 * Construct plugin handler
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$pluginsTableQuery = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_PLUGINS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`className` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`active` tinyint(1) NOT NULL DEFAULT '0',
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ManiaControl plugin status' AUTO_INCREMENT=1;";
		$tableStatement = $mysqli->prepare($pluginsTableQuery);
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
	 * Save plugin status in database
	 *
	 * @param string $className        	
	 * @param bool $active        	
	 * @return bool
	 */
	private function savePluginStatus($className, $active) {
		$mysqli = $this->maniaControl->database->mysqli;
		$pluginStatusQuery = "INSERT INTO `" . self::TABLE_PLUGINS . "` (
				`className`,
				`active`
				) VALUES (
				?, ?
				) ON DUPLICATE KEY UPDATE
				`active` = VALUES(`active`);";
		$pluginStatement = $mysqli->prepare($pluginStatusQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$activeInt = ($active ? 1 : 0);
		$pluginStatement->bind_param('si', $className, $activeInt);
		$pluginStatement->execute();
		if ($pluginStatement->error) {
			trigger_error($pluginStatement->error);
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
	private function getPluginStatus($className) {
		$mysqli = $this->maniaControl->database->mysqli;
		$pluginStatusQuery = "SELECT `active` FROM `" . self::TABLE_PLUGINS . "`
				WHERE `className` = ?;";
		$pluginStatement = $mysqli->prepare($pluginStatusQuery);
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
	 * Load complete plugins directory and start all configured plugins
	 */
	public function loadPlugins() {
		$pluginsDirectory = ManiaControlDir . '/plugins/';
		$pluginFiles = scandir($pluginsDirectory, 0);
		foreach ($pluginFiles as $pluginFile) {
			if (stripos($pluginFile, '.') === 0) {
				continue;
			}
			$classesBefore = get_declared_classes();
			$success = include_once $pluginsDirectory . $pluginFile;
			if (!$success) {
				continue;
			}
			$classesAfter = get_declared_classes();
			$newClasses = array_diff($classesAfter, $classesBefore);
			foreach ($newClasses as $className) {
				if (!is_subclass_of($className, 'ManiaControl\Plugin')) {
					continue;
				}
				array_push($this->pluginClasses, $className);
				$active = $this->getPluginStatus($className);
				if (!$active) {
					continue;
				}
				$plugin = new $className($this->maniaControl);
				array_push($this->activePlugins, $plugin);
			}
		}
	}
}

?>

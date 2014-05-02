<?php

namespace ManiaControl\Settings;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\PluginManager;

/**
 * Class managing Settings and Configurations
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class SettingManager implements CallbackListener {
	/*
	 * Constants
	 */
	const TABLE_SETTINGS      = 'mc_settings';
	const TYPE_STRING         = 'string';
	const TYPE_INT            = 'int';
	const TYPE_REAL           = 'real';
	const TYPE_BOOL           = 'bool';
	const TYPE_ARRAY          = 'array';
	const CB_SETTINGS_CHANGED = 'SettingManager.SettingsChanged';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $arrayDelimiter = ';;';
	private $storedSettings = array();

	/**
	 * Construct a new Setting Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_AFTERINIT, $this, 'handleAfterInit');
	}

	/**
	 * Initialize necessary Database Tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli            = $this->maniaControl->database->mysqli;
		$defaultType       = "'" . self::TYPE_STRING . "'";
		$typeSet           = $defaultType . ",'" . self::TYPE_INT . "','" . self::TYPE_REAL . "','" . self::TYPE_BOOL . "','" . self::TYPE_ARRAY . "'";
		$settingTableQuery = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SETTINGS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`class` varchar(100) NOT NULL,
				`setting` varchar(150) NOT NULL,
				`type` set({$typeSet}) NOT NULL DEFAULT {$defaultType},
				`value` varchar(100) NOT NULL,
				`default` varchar(100) NOT NULL,
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `settingId` (`class`,`setting`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Settings and Configurations' AUTO_INCREMENT=1;";
		$result            = $mysqli->query($settingTableQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
		}

		return $result;
	}

	/**
	 * Handle After Init Callback
	 */
	public function handleAfterInit() {
		$this->deleteUnusedSettings();
	}

	/**
	 * Delete all unused Settings that haven't been initialized during the current Startup
	 *
	 * @return bool
	 */
	private function deleteUnusedSettings() {
		$mysqli           = $this->maniaControl->database->mysqli;
		$settingQuery     = "DELETE FROM `" . self::TABLE_SETTINGS . "`
				WHERE `changed` < NOW() - INTERVAL 1 HOUR;";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$success = $settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$settingStatement->close();
		$this->storedSettings = array();
		return $success;
	}

	/**
	 * Initialize a Setting for the given Object
	 *
	 * @param mixed  $object
	 * @param string $settingName
	 * @param mixed  $default
	 * @return bool
	 */
	public function initSetting($object, $settingName, $default) {
		if (is_null($default) || is_object($default)) {
			return false;
		}
		$className        = $this->getClassName($object);
		$type             = $this->getType($default);
		$default          = $this->formatSetting($default, $type);
		$mysqli           = $this->maniaControl->database->mysqli;
		$settingQuery     = "INSERT INTO `" . self::TABLE_SETTINGS . "` (
				`class`,
				`setting`,
				`type`,
				`value`,
				`default`
				) VALUES (
				?, ?, ?,
				@value := ?,
				@value
				) ON DUPLICATE KEY UPDATE
				`type` = VALUES(`type`),
				`value` = IF(`default` = VALUES(`default`), `value`, VALUES(`default`)),
				`default` = VALUES(`default`),
				`changed` = NOW();";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$settingStatement->bind_param('ssss', $className, $settingName, $type, $default);
		$success = $settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$settingStatement->close();
		return $success;
	}

	/**
	 * Get Class Name of a Parameter
	 *
	 * @param mixed $param
	 * @return string
	 */
	private function getClassName($param) {
		if (is_object($param)) {
			return get_class($param);
		}
		if (is_string($param)) {
			return $param;
		}
		trigger_error('Invalid class param. ' . $param);
		return (string)$param;
	}

	/**
	 * Get Type of a Parameter
	 *
	 * @param mixed $param
	 * @return string
	 */
	private function getType($param) {
		if (is_int($param)) {
			return self::TYPE_INT;
		}
		if (is_real($param)) {
			return self::TYPE_REAL;
		}
		if (is_bool($param)) {
			return self::TYPE_BOOL;
		}
		if (is_string($param)) {
			return self::TYPE_STRING;
		}
		if (is_array($param)) {
			return self::TYPE_ARRAY;
		}
		trigger_error('Unsupported setting type. ' . print_r($param, true));
		return null;
	}

	/**
	 * Format a Setting for saving it to the Database
	 *
	 * @param mixed  $value
	 * @param string $type
	 * @return mixed
	 */
	private function formatSetting($value, $type = null) {
		if ($type === null) {
			$type = $this->getType($value);
		}
		if ($type === self::TYPE_ARRAY) {
			return implode($this->arrayDelimiter, $value);
		}
		if ($type === self::TYPE_BOOL) {
			return ($value ? 1 : 0);
		}
		return $value;
	}

	/**
	 * Get a Setting by its Index
	 *
	 * @param int   $settingIndex
	 * @param mixed $default
	 * @return mixed
	 */
	public function getSettingByIndex($settingIndex, $default = false) {
		$mysqli       = $this->maniaControl->database->mysqli;
		$settingQuery = "SELECT * FROM `" . self::TABLE_SETTINGS . "`
				WHERE `index` = {$settingIndex};";
		$result       = $mysqli->query($settingQuery);
		if (!$result) {
			trigger_error($mysqli->error);
			return null;
		}
		if ($result->num_rows <= 0) {
			$result->close();
			return $default;
		}
		$row = $result->fetch_object();
		$result->close();
		return $row;
	}

	/**
	 * Get Setting by Name for the given Object
	 *
	 * @param mixed  $object
	 * @param string $settingName
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getSetting($object, $settingName, $default = null) {
		$className = $this->getClassName($object);

		// Check if setting is already in the ram
		if (isset($this->storedSettings[$className . $settingName])) {
			return $this->storedSettings[$className . $settingName];
		}

		$mysqli           = $this->maniaControl->database->mysqli;
		$settingQuery     = "SELECT `type`, `value` FROM `" . self::TABLE_SETTINGS . "`
				WHERE `class` = ?
				AND `setting` = ?;";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		$settingStatement->bind_param('ss', $className, $settingName);
		$settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
			return null;
		}
		$settingStatement->store_result();
		if ($settingStatement->num_rows <= 0) {
			$this->setSetting($className, $settingName, $default);
			return $default;
		}
		$settingStatement->bind_result($type, $value);
		$settingStatement->fetch();
		$settingStatement->free_result();
		$settingStatement->close();
		$setting = $this->castSetting($type, $value);

		// Store setting in the ram
		$this->storedSettings[$className . $settingName] = $setting;
		return $setting;
	}

	/**
	 * Set a Setting for the given Object
	 *
	 * @param mixed  $object
	 * @param string $settingName
	 * @param mixed  $value
	 * @return bool
	 */
	public function setSetting($object, $settingName, $value) {
		$className = $this->getClassName($object);

		$mysqli           = $this->maniaControl->database->mysqli;
		$settingQuery     = "UPDATE `" . self::TABLE_SETTINGS . "`
				SET `value` = ?
				WHERE `class` = ?
				AND `setting` = ?;";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$setting = $this->formatSetting($value);
		$settingStatement->bind_param('sss', $setting, $className, $settingName);
		$success = $settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$settingStatement->close();

		$this->storedSettings[$className . $settingName] = $value;

		// Trigger settings changed Callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_SETTINGS_CHANGED, $className, $settingName, $value);
		return $success;
	}

	/**
	 * Cast a Setting to the given Type
	 *
	 * @param string $type
	 * @param mixed  $value
	 * @return mixed
	 */
	private function castSetting($type, $value) {
		if ($type === self::TYPE_INT) {
			return (int)$value;
		}
		if ($type === self::TYPE_REAL) {
			return (float)$value;
		}
		if ($type === self::TYPE_BOOL) {
			return (bool)$value;
		}
		if ($type === self::TYPE_STRING) {
			return (string)$value;
		}
		if ($type === self::TYPE_ARRAY) {
			return explode($this->arrayDelimiter, $value);
		}
		trigger_error('Unsupported setting type. ' . print_r($type, true));
		return $value;
	}

	/**
	 * Reset a Setting to its default Value
	 *
	 * @param mixed  $object
	 * @param string $settingName
	 * @return bool
	 */
	public function resetSetting($object, $settingName) {
		$className        = $this->getClassName($object);
		$mysqli           = $this->maniaControl->database->mysqli;
		$settingQuery     = "UPDATE `" . self::TABLE_SETTINGS . "`
				SET `value` = `default`
				WHERE `class` = ?
				AND `setting` = ?;";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$settingStatement->bind_param('ss', $className, $settingName);
		$success = $settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$settingStatement->close();
		if (isset($this->storedSettings[$className . $settingName])) {
			unset($this->storedSettings[$className . $settingName]);
		}
		return $success;
	}

	/**
	 * Delete a Setting
	 *
	 * @param mixed  $object
	 * @param string $settingName
	 * @return bool
	 */
	public function deleteSetting($object, $settingName) {
		$className        = $this->getClassName($object);
		$mysqli           = $this->maniaControl->database->mysqli;
		$settingQuery     = "DELETE FROM `" . self::TABLE_SETTINGS . "`
				WHERE `class` = ?
				AND `setting` = ?;";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$settingStatement->bind_param('ss', $className, $settingName);
		$success = $settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$settingStatement->close();
		if (isset($this->storedSettings[$className . $settingName])) {
			unset($this->storedSettings[$className . $settingName]);
		}
		return $success;
	}

	/**
	 * Get all Settings for the given Class
	 *
	 * @param mixed $object
	 * @return array
	 */
	public function getSettingsByClass($object) {
		$className = $this->getClassName($object);
		$mysqli    = $this->maniaControl->database->mysqli;
		$query     = "SELECT * FROM `" . self::TABLE_SETTINGS . "` WHERE `class` = '" . $mysqli->escape_string($className) . "'
				ORDER BY `setting` ASC;";
		$result    = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		$settings = array();
		while ($setting = $result->fetch_object()) {
			$settings[$setting->index] = $setting;
		}
		$result->free();
		return $settings;
	}

	/**
	 * Get all settings
	 *
	 * @return array
	 */
	public function getSettings() {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "SELECT * FROM `" . self::TABLE_SETTINGS . "`
				ORDER BY `class` ASC, `setting` ASC;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		$settings = array();
		while ($setting = $result->fetch_object()) {
			$settings[$setting->index] = $setting;
		}
		$result->free();
		return $settings;
	}

	/**
	 * Get all Setting Classes
	 *
	 * @param bool $hidePluginClasses
	 * @return array
	 */
	public function getSettingClasses($hidePluginClasses = false) {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "SELECT DISTINCT `class` FROM `" . self::TABLE_SETTINGS . "`
				ORDER BY `class` ASC;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		$settingClasses = array();
		while ($setting = $result->fetch_object()) {
			if (!$hidePluginClasses || !PluginManager::isPluginClass($setting->class)) {
				array_push($settingClasses, $setting->class);
			}
		}
		$result->free();
		return $settingClasses;
	}
}

<?php

namespace ManiaControl;

/**
 * Class managing settings and configurations
 *
 * @author steeffeen & kremsy
 */
class SettingManager {
	/**
	 * Constants
	 */
	const TABLE_SETTINGS = 'mc_settings';
	const TYPE_STRING    = 'string';
	const TYPE_INT       = 'int';
	const TYPE_REAL      = 'real';
	const TYPE_BOOL      = 'bool';
	const TYPE_ARRAY     = 'array';
	//const TYPE_AUTH_LEVEL = 'auth'; //TODO

	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $arrayDelimiter = ';;';

	/**
	 * Construct setting manager
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
		$mysqli      = $this->maniaControl->database->mysqli;
		$defaultType = "'" . self::TYPE_STRING . "'";
		$typeSet     = $defaultType;
		$typeSet .= ",'" . self::TYPE_INT . "'";
		$typeSet .= ",'" . self::TYPE_REAL . "'";
		$typeSet .= ",'" . self::TYPE_BOOL . "'";
		$typeSet .= ",'" . self::TYPE_ARRAY . "'";
		$settingTableQuery     = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SETTINGS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`class` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`setting` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`type` set({$typeSet}) COLLATE utf8_unicode_ci NOT NULL DEFAULT {$defaultType},
				`value` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`default` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `settingId` (`class`,`setting`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Settings and Configurations' AUTO_INCREMENT=1;";
		$settingTableStatement = $mysqli->prepare($settingTableQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$settingTableStatement->execute();
		if($settingTableStatement->error) {
			trigger_error($settingTableStatement->error, E_USER_ERROR);
			return false;
		}
		$settingTableStatement->close();
		return true;
	}

	/**
	 * Get class name from param
	 *
	 * @param mixed $object
	 * @return string
	 */
	private function getClassName($object) {
		if(is_object($object)) {
			return get_class($object);
		}
		if(is_string($object)) {
			return $object;
		}
		trigger_error('Invalid class param. ' . $object);
		return (string)$object;
	}

	/**
	 * Get type for given parameter
	 *
	 * @param mixed $param
	 * @return string
	 */
	private function getType($param) {
		if(is_int($param)) {
			return self::TYPE_INT;
		}
		if(is_real($param)) {
			return self::TYPE_REAL;
		}
		if(is_bool($param)) {
			return self::TYPE_BOOL;
		}
		if(is_string($param)) {
			return self::TYPE_STRING;
		}
		if(is_array($param)) {
			return self::TYPE_ARRAY;
		}
		trigger_error('Unsupported setting type. ' . print_r($param, true));
		return null;
	}

	/**
	 * Cast a setting to the given type
	 *
	 * @param string $type
	 * @param mixed  $value
	 * @return mixed
	 */
	private function castSetting($type, $value) {
		if($type === self::TYPE_INT) {
			return (int)$value;
		}
		if($type === self::TYPE_REAL) {
			return (float)$value;
		}
		if($type === self::TYPE_BOOL) {
			return (bool)$value;
		}
		if($type === self::TYPE_STRING) {
			return (string)$value;
		}
		if($type === self::TYPE_ARRAY) {
			return explode($this->arrayDelimiter, $value);
		}
		trigger_error('Unsupported setting type. ' . print_r($type, true));
		return $value;
	}

	/**
	 * Format a setting for saving it to the database
	 *
	 * @param mixed  $value
	 * @param string $type
	 * @return mixed
	 */
	private function formatSetting($value, $type = null) {
		if($type === null) {
			$type = $this->getType($value);
		}
		if($type === self::TYPE_ARRAY) {
			return implode($this->arrayDelimiter, $value);
		}
		if($type === self::TYPE_BOOL) {
			return ($value ? 1 : 0);
		}
		return $value;
	}

	/**
	 * Initialize a setting for given object
	 *
	 * @param object $object
	 * @param string $settingName
	 * @param mixed  $default
	 * @return bool
	 */
	public function initSetting($object, $settingName, $default) {
		if($default === null || is_object($default)) {
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
				`default` = VALUES(`default`);";
		$settingStatement = $mysqli->prepare($settingQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$settingStatement->bind_param('ssss', $className, $settingName, $type, $default);
		$success = $settingStatement->execute();
		if($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$settingStatement->close();
		return $success;
	}

	/**
	 * Get a Setting by its index
	 *
	 * @param      $settingIndex
	 * @param bool $default
	 * @internal param null $default
	 * @internal param $className
	 * @internal param \ManiaControl\gIndex $settin
	 * @return mixed|null
	 */
	public function getSettingByIndex($settingIndex, $default = false) {
		$mysqli       = $this->maniaControl->database->mysqli;
		$settingQuery = "SELECT * FROM `" . self::TABLE_SETTINGS . "`
				WHERE `index` = {$settingIndex};";
		$result       = $mysqli->query($settingQuery);
		if(!$result) {
			trigger_error($mysqli->error);
			return false;
		}

		$row = $result->fetch_object();
		$result->close();
		return $row;
	}


	/**
	 * Gets a Setting Via it's class name
	 *
	 * @param $className
	 * @param $settingName
	 * @param $value
	 */
	public function getSettingByClassName($className, $settingName, $default = null) {
		$mysqli           = $this->maniaControl->database->mysqli;
		$settingQuery     = "SELECT `type`, `value` FROM `" . self::TABLE_SETTINGS . "`
				WHERE `class` = ?
				AND `setting` = ?;";
		$settingStatement = $mysqli->prepare($settingQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		$settingStatement->bind_param('ss', $className, $settingName);
		$settingStatement->execute();
		if($settingStatement->error) {
			trigger_error($settingStatement->error);
			return null;
		}
		$settingStatement->store_result();
		if($settingStatement->num_rows <= 0) {
			$this->updateSetting($className, $settingName, $default);
			return $default;
		}
		$settingStatement->bind_result($type, $value);
		$settingStatement->fetch();
		$settingStatement->free_result();
		$settingStatement->close();
		$setting = $this->castSetting($type, $value);
		return $setting;
	}

	/**
	 * Get setting by name for given object
	 *
	 * @param object $object
	 * @param string $settingName
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getSetting($object, $settingName, $default = null) {
		$className = $this->getClassName($object);
		return $this->getSettingByClassName($className, $settingName, $default);
	}

	/**
	 * Updates a Setting
	 *
	 * @param $className
	 * @param $settingName
	 * @param $value
	 * @return bool
	 */
	public function updateSetting($className, $settingName, $value) {
		$mysqli           = $this->maniaControl->database->mysqli;
		$settingQuery     = "UPDATE `" . self::TABLE_SETTINGS . "`
				SET `value` = ?
				WHERE `class` = ?
				AND `setting` = ?;";
		$settingStatement = $mysqli->prepare($settingQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$value = $this->formatSetting($value);
		$settingStatement->bind_param('sss', $value, $className, $settingName);
		$success = $settingStatement->execute();
		if($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$settingStatement->close();
		return $success;
	}

	/**
	 * Set a setting for the given object
	 *
	 * @param object $object
	 * @param string $settingName
	 * @param mixed  $value
	 * @return bool
	 */
	public function setSetting($object, $settingName, $value) {
		$className = $this->getClassName($object);
		$this->updateSetting($className, $settingName, $value);
	}

	/**
	 * Reset a setting to its default value
	 *
	 * @param object $object
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
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$settingStatement->bind_param('ss', $className, $settingName);
		$success = $settingStatement->execute();
		if($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$settingStatement->close();
		return $success;
	}

	/**
	 * Delete a setting from the database
	 *
	 * @param object $object
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
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$settingStatement->bind_param('ss', $className, $settingName);
		$success = $settingStatement->execute();
		if($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$settingStatement->close();
		return $success;
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
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		$settings = array();
		while($setting = $result->fetch_object()) {
			$settings[$setting->index] = $setting;
			//array_push($settings, $setting);
		}
		$result->free();
		return $settings;
	}
}

<?php

namespace ManiaControl;

require_once __DIR__ . '/SettingConfigurator.php';

use ManiaControl\Settings\SettingConfigurator;

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
	const TYPE_STRING = 'string';
	const TYPE_INT = 'int';
	const TYPE_REAL = 'real';
	const TYPE_BOOL = 'bool';
	const TYPE_ARRAY = 'array';
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $configurator = null;
	private $arrayDelimiter = ';;';

	/**
	 * Construct setting manager
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		$this->initTables();
		
		$this->configurator = new SettingConfigurator($maniaControl);
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$defaultType = "'" . self::TYPE_STRING . "'";
		$typeSet = $defaultType;
		$typeSet .= ",'" . self::TYPE_INT . "'";
		$typeSet .= ",'" . self::TYPE_REAL . "'";
		$typeSet .= ",'" . self::TYPE_BOOL . "'";
		$typeSet .= ",'" . self::TYPE_ARRAY . "'";
		$settingTableQuery = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SETTINGS . "` (
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
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$settingTableStatement->execute();
		if ($settingTableStatement->error) {
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
		if (is_object($object)) {
			return get_class($object);
		}
		if (is_string($object)) {
			return $object;
		}
		trigger_error('Invalid class param. ' . $object);
		return (string) $object;
	}

	/**
	 * Get type for given parameter
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
	 * Cast a setting to the given type
	 *
	 * @param string $type        	
	 * @param mixed $value        	
	 * @return mixed
	 */
	private function castSetting($type, $value) {
		if ($type === self::TYPE_INT) {
			return (int) $value;
		}
		if ($type === self::TYPE_REAL) {
			return (real) $value;
		}
		if ($type === self::TYPE_BOOL) {
			return (bool) $value;
		}
		if ($type === self::TYPE_STRING) {
			return (string) $value;
		}
		if ($type === self::TYPE_ARRAY) {
			return explode($this->arrayDelimiter, $value);
		}
		trigger_error('Unsupported setting type. ' . print_r($param, true));
		return $value;
	}

	/**
	 * Format a setting for saving it to the database
	 *
	 * @param mixed $value        	
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
		return $value;
	}

	/**
	 * Initialize a setting for given object
	 *
	 * @param object $object        	
	 * @param string $settingName        	
	 * @param mixed $default        	
	 * @return bool
	 */
	public function initSetting($object, $settingName, $default) {
		if ($default === null || is_object($default)) {
			return false;
		}
		$className = $this->getClassName($object);
		$type = $this->getType($default);
		$default = $this->formatSetting($default, $type);
		$mysqli = $this->maniaControl->database->mysqli;
		$settingQuery = "INSERT INTO `" . self::TABLE_SETTINGS . "` (
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
				`default` = VALUES(`default`);";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$settingStatement->bind_param('ssss', $className, $settingName, $type, $default);
		$settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$settingStatement->close();
		return true;
	}

	/**
	 * Get setting by name for given object
	 *
	 * @param object $object        	
	 * @param string $settingName        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public function getSetting($object, $settingName, $default = null) {
		$className = $this->getClassName($object);
		$mysqli = $this->maniaControl->database->mysqli;
		$settingQuery = "SELECT `type`, `value` FROM `" . self::TABLE_SETTINGS . "`
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
			$this->initSetting($object, $settingName, $default);
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
	 * Set a setting for the given object
	 *
	 * @param object $object        	
	 * @param string $settingName        	
	 * @param mixed $value        	
	 * @return bool
	 */
	public function setSetting($object, $settingName, $value) {
		$className = $this->getClassName($object);
		$mysqli = $this->maniaControl->database->mysqli;
		$settingQuery = "UPDATE `" . self::TABLE_SETTINGS . "`
				SET `value` = ?
				WHERE `class` = ?
				AND `setting` = ?;";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$value = $this->formatSetting($value);
		$settingStatement->bind_param('sss', $value, $className, $settingName);
		$settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
			return false;
		}
		$settingStatement->close();
		return true;
	}

	/**
	 * Reset a setting to its default value
	 *
	 * @param object $object        	
	 * @param string $settingname        	
	 * @return bool
	 */
	public function resetSetting($object, $settingname) {
		$className = $this->getClassName($object);
		$mysqli = $this->maniaControl->database->mysqli;
		$settingQuery = "UPDATE `" . self::TABLE_SETTINGS . "`
				SET `value` = `default`
				WHERE `class` = ?
				AND `setting` = ?;";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$settingStatement->bind_param('ss', $className, $settingName);
		$settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
			return false;
		}
		$settingStatement->close();
		return true;
	}
}

?>

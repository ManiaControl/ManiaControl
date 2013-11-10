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
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;

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
		$mysqli = $this->maniaControl->database->mysqli;
		$settingTableQuery = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SETTINGS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`class` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`setting` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`type` set('string','int','real','bool') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'string',
				`value` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`default` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
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
	 * Get type for given parameter
	 *
	 * @param mixed $param        	
	 * @return string
	 */
	private function getType($param) {
		if (is_int($param)) {
			return 'int';
		}
		if (is_real($param)) {
			return 'real';
		}
		if (is_bool($param)) {
			return 'bool';
		}
		if (is_string($param)) {
			return 'string';
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
		$type = strtolower($type);
		if ($type === 'int') {
			return (int) $value;
		}
		if ($type === 'real') {
			return (real) $value;
		}
		if ($type === 'bool') {
			return (bool) $value;
		}
		if ($type === 'string') {
			return (string) $value;
		}
		trigger_error('Unsupported setting type. ' . print_r($param, true));
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
		$className = $object;
		if (is_object($object)) {
			$className = get_class($object);
		}
		$type = $this->getType($default);
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
				);";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$settingStatement->bind_param('ssss', $className, $settingName, $type, $default);
		$settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
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
		$className = $object;
		if (is_object($object)) {
			$className = get_class($object);
		}
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
}

?>

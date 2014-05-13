<?php

namespace ManiaControl\Settings;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\PluginManager;
use ManiaControl\Utils\ClassUtil;

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
	const TABLE_SETTINGS     = 'mc_settings';
	const CB_SETTING_CHANGED = 'SettingManager.SettingChanged';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
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
	 * Initialize the necessary Database Tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli            = $this->maniaControl->database->mysqli;
		$defaultType       = "'" . Setting::TYPE_STRING . "'";
		$typeSet           = Setting::getTypeSet();
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
		$mysqli       = $this->maniaControl->database->mysqli;
		$settingQuery = "DELETE FROM `" . self::TABLE_SETTINGS . "`
				WHERE `changed` < NOW() - INTERVAL 1 HOUR;";
		$result       = $mysqli->query($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		if ($result) {
			$this->storedSettings = array();
			return true;
		}
		return false;
	}

	/**
	 * Get a Setting by its Index
	 *
	 * @param int   $settingIndex
	 * @param mixed $defaultValue
	 * @return Setting
	 */
	public function getSettingByIndex($settingIndex, $defaultValue = null) {
		$mysqli       = $this->maniaControl->database->mysqli;
		$settingQuery = "SELECT * FROM `" . self::TABLE_SETTINGS . "`
				WHERE `index` = {$settingIndex};";
		$result       = $mysqli->query($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		if ($result->num_rows <= 0) {
			$result->close();
			return $defaultValue;
		}

		/** @var Setting $setting */
		$setting = $result->fetch_object(Setting::CLASS_NAME, array(true));
		$result->close();

		return $setting;
	}

	/**
	 * Set a Setting for the given Object
	 *
	 * @param mixed  $object
	 * @param string $settingName
	 * @param mixed  $value
	 * @return Setting
	 */
	public function setSetting($object, $settingName, $value) {
		$className = ClassUtil::getClass($object);

		$setting = $this->getSetting($object, $settingName);
		if ($setting) {
			$setting->value = $value;
			if (!$this->saveSetting($setting)) {
				return null;
			}
		} else {
			$setting = $this->initSetting($object, $settingName, $value);
		}

		$this->storedSettings[$className . $settingName] = $setting;

		// Trigger Settings Changed Callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_SETTING_CHANGED, $setting);

		return $setting;
	}

	/**
	 * Get Setting by Name for the given Object
	 *
	 * @param mixed  $object
	 * @param string $settingName
	 * @param mixed  $default
	 * @return Setting
	 */
	public function getSetting($object, $settingName, $default = null) {
		$settingClass = ClassUtil::getClass($object);

		// Retrieve from Storage if possible
		if (isset($this->storedSettings[$settingClass . $settingName])) {
			return $this->storedSettings[$settingClass . $settingName];
		}

		// Fetch setting
		$mysqli       = $this->maniaControl->database->mysqli;
		$settingQuery = "SELECT * FROM `" . self::TABLE_SETTINGS . "`
				WHERE `class` = '" . $mysqli->escape_string($settingClass) . "'
				AND `setting` = '" . $mysqli->escape_string($settingName) . "';";
		$result       = $mysqli->query($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		if ($result->num_rows <= 0) {
			if ($default === null) {
				return null;
			}
			$setting = $this->initSetting($object, $settingName, $default);
			return $setting;
		}

		/** @var Setting $setting */
		$setting = $result->fetch_object(Setting::CLASS_NAME, array(true));
		$result->free();

		// Store setting
		$this->storedSettings[$settingClass . $settingName] = $setting;

		return $setting;
	}

	/**
	 * Initialize a Setting for the given Object
	 *
	 * @param mixed  $object
	 * @param string $settingName
	 * @param mixed  $defaultValue
	 * @return Setting
	 */
	public function initSetting($object, $settingName, $defaultValue) {
		$setting          = new Setting();
		$setting->class   = ClassUtil::getClass($object);
		$setting->setting = $settingName;
		$setting->type    = Setting::getValueType($defaultValue);
		$setting->value   = $defaultValue;
		$setting->default = $defaultValue;
		$saved            = $this->saveSetting($setting);
		if ($saved) {
			return $setting;
		}
		return null;
	}

	/**
	 * Save the given Setting in the Database
	 *
	 * @param Setting $setting
	 * @return bool
	 */
	public function saveSetting(Setting &$setting) {
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
				`index` = LAST_INSERT_ID(`index`),
				`type` = VALUES(`type`),
				`value` = IF(`default` = VALUES(`default`), `value`, VALUES(`default`)),
				`default` = VALUES(`default`),
				`changed` = NOW();";
		$settingStatement = $mysqli->prepare($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$formattedValue = $setting->getFormattedValue();
		$settingStatement->bind_param('ssss', $setting->class, $setting->setting, $setting->type, $formattedValue);
		$settingStatement->execute();
		if ($settingStatement->error) {
			trigger_error($settingStatement->error);
			$settingStatement->close();
			return false;
		}
		$setting->index = $settingStatement->insert_id;
		$settingStatement->close();
		return true;
	}

	/**
	 * Reset a Setting to its Default Value
	 *
	 * @param mixed  $object
	 * @param string $settingName
	 * @return bool
	 */
	public function resetSetting($object, $settingName = null) {
		if ($object instanceof Setting) {
			$className   = $object->class;
			$settingName = $object->setting;
		} else {
			$className = ClassUtil::getClass($object);
		}
		$mysqli       = $this->maniaControl->database->mysqli;
		$settingQuery = "UPDATE `" . self::TABLE_SETTINGS . "`
				SET `value` = `default`
				WHERE `class` = '" . $mysqli->escape_string($className) . "'
				AND `setting` = '" . $mysqli->escape_string($settingName) . "';";
		$result       = $mysqli->query($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		if (isset($this->storedSettings[$className . $settingName])) {
			unset($this->storedSettings[$className . $settingName]);
		}
		return $result;
	}

	/**
	 * Delete a Setting
	 *
	 * @param mixed  $object
	 * @param string $settingName
	 * @return bool
	 */
	public function deleteSetting($object, $settingName = null) {
		if ($object instanceof Setting) {
			$className   = $object->class;
			$settingName = $object->setting;
		} else {
			$className = ClassUtil::getClass($object);
		}

		$mysqli       = $this->maniaControl->database->mysqli;
		$settingQuery = "DELETE FROM `" . self::TABLE_SETTINGS . "`
				WHERE `class` = '" . $mysqli->escape_string($className) . "'
				AND `setting` = '" . $mysqli->escape_string($settingName) . "';";
		$result       = $mysqli->query($settingQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}

		if (isset($this->storedSettings[$className . $settingName])) {
			unset($this->storedSettings[$className . $settingName]);
		}

		return $result;
	}

	/**
	 * Get all Settings for the given Class
	 *
	 * @param mixed $object
	 * @return array
	 */
	public function getSettingsByClass($object) {
		$className = ClassUtil::getClass($object);
		$mysqli    = $this->maniaControl->database->mysqli;
		$query     = "SELECT * FROM `" . self::TABLE_SETTINGS . "`
				WHERE `class` = '" . $mysqli->escape_string($className) . "'
				ORDER BY `setting` ASC;";
		$result    = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		$settings = array();
		while ($setting = $result->fetch_object(Setting::CLASS_NAME, array(true))) {
			$settings[$setting->index] = $setting;
		}
		$result->free();
		return $settings;
	}

	/**
	 * Get all Settings
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
		while ($setting = $result->fetch_object(Setting::CLASS_NAME, array(true))) {
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
		while ($row = $result->fetch_object()) {
			if (!$hidePluginClasses || !PluginManager::isPluginClass($row->class)) {
				array_push($settingClasses, $row->class);
			}
		}
		$result->free();
		return $settingClasses;
	}
}

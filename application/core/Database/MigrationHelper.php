<?php

namespace ManiaControl\Database;

use ManiaControl\ManiaControl;
use ManiaControl\Settings\SettingManager;

/**
 * Database Migration Assistant
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MigrationHelper {
	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Construct Migration Helper
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Transfer the Settings of the given Class to a new One
	 *
	 * @param mixed $sourceClass
	 * @param mixed $targetClass
	 * @return bool
	 */
	public function transferSettings($sourceClass, $targetClass) {
		$sourceClass = $this->getClass($sourceClass);
		$targetClass = $this->getClass($targetClass);

		$mysqli = $this->maniaControl->database->mysqli;

		$query     = "INSERT IGNORE INTO `" . SettingManager::TABLE_SETTINGS . "`
				(`class`, `setting`, `type`, `value`, `default`)
				SELECT ?, `setting`, `type`, `value`, `default`
				FROM `" . SettingManager::TABLE_SETTINGS . "`
				WHERE `class` = ?;";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$statement->bind_param('ss', $targetClass, $sourceClass);
		$success = $statement->execute();
		if ($statement->error) {
			trigger_error($statement->error);
			$statement->close();
			return false;
		}
		$statement->close();
		return $success;
	}

	/**
	 * Get the Class of the given Object
	 *
	 * @param mixed $class
	 * @return string
	 */
	private function getClass($class) {
		if (is_object($class)) {
			return get_class($class);
		}
		return (string)$class;
	}
}

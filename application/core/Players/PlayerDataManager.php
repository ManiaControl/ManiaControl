<?php

namespace ManiaControl\Players;


use ManiaControl\ManiaControl;

/**
 * Player Data Manager
 *
 * @author    steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerDataManager {
	const TABLE_PLAYERDATAMETADATA = 'mc_playerdata_metadata';
	const TABLE_PLAYERDATA         = 'mc_playerdata';
	const TYPE_STRING              = 'string';
	const TYPE_INT                 = 'int';
	const TYPE_REAL                = 'real';
	const TYPE_BOOL                = 'bool';
	const TYPE_ARRAY               = 'array';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $arrayDelimiter = ';;';
	private $metaData = array();

	/**
	 * Construct player manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		// Store Stats MetaData
		//$this->storeMetaData();
	}

	/**
	 * Defines the Player-Data MetaData
	 *
	 * @param $object
	 * @param $dataName
	 * @param $default
	 * @param $dataDescription (optional)
	 * @return bool
	 */
	public function defineMetaData($object, $dataName, $default, $dataDescription = '') {
		$mysqli    = $this->maniaControl->database->mysqli;
		$className        = $this->getClassName($object);

		$query     = "INSERT INTO `" . self::TABLE_PLAYERDATAMETADATA . "` (
				`class`,
				`dataName`,
				`type`,
				`defaultValue`,
				`description`
				) VALUES (
				?, ?, ?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`type` = VALUES(`type`),
				`defaultValue` = VALUES(`defaultValue`),
				`description` = VALUES(`description`);";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$type = $this->getType($default);

		$statement->bind_param('sssss', $className, $dataName, $type, $default, $dataDescription);
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error);
			$statement->close();
			return false;
		}
		$statement->close();
		return true;
	}

	/**
	 * Store Meta Data from the Database in the Ram
	 */
	private function storeMetaData() {
		$mysqli = $this->maniaControl->database->mysqli;

		$query  = "SELECT * FROM `" . self::TABLE_PLAYERDATAMETADATA . "`;";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return;
		}

		while($row = $result->fetch_object()) {
			$this->metaData[$row->class . $row->dataName] = $row;
		}
		$result->close();
	}


	/**
	 * Inserts a PlayerData to a specific defined statMetaData
	 *
	 * @param        $object
	 * @param        $statName
	 * @param Player $player
	 * @param        $value
	 * @param        $serverIndex (let it empty if its global)
	 * @return bool
	 */
	public function insertPlayerData($object, $statName, Player $player, $value, $serverIndex = -1) {
		$className        = $this->getClassName($object);
		if (!$player) {
			return false;
		}

		$dataId = $this->getMetaDataId($className, $statName);
		if (!$dataId) {
			return false;
		}

		$mysqli    = $this->maniaControl->database->mysqli;
		$query     = "INSERT INTO `" . self::TABLE_PLAYERDATA . "` (
				`serverIndex`,
				`playerId`,
				`dataId`,
				`value`
				) VALUES (
				?, ?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`value` = VALUES(`value`);";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$statement->bind_param('iiis', $serverIndex, $player->index, $dataId, $value);
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error);
			$statement->close();
			return false;
		}
		$statement->close();
		return true;
	}

	/**
	 * Return the Id of the MetaData
	 *
	 * @param $statName
	 * @return int
	 */
	private function getMetaDataId($className, $statName) {
		if (isset($this->metaData[$className . $statName])) {
			$stat = $this->metaData[$className . $statName];
			return (int)$stat->dataId;
		}
		return null;
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli      = $this->maniaControl->database->mysqli;
		$defaultType = "'" . self::TYPE_STRING . "'";
		$typeSet     = $defaultType . ",'" . self::TYPE_INT . "','" . self::TYPE_REAL . "','" . self::TYPE_BOOL . "','" . self::TYPE_ARRAY . "'";
		$query       = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_PLAYERDATAMETADATA . "` (
				`dataId` int(11) NOT NULL AUTO_INCREMENT,
				`class` varchar(100) NOT NULL,
				`dataName` varchar(100) NOT NULL,
				`type` set({$typeSet}) NOT NULL DEFAULT {$defaultType},
				`defaultValue` varchar(150) NOT NULL,
				`description` varchar(150) NOT NULL,
				PRIMARY KEY (`dataId`),
				UNIQUE KEY `name` (`class`, `dataName`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Player-Data MetaData' AUTO_INCREMENT=1;";
		$statement   = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error, E_USER_ERROR);
			return false;
		}
		$statement->close();

		$query     = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_PLAYERDATA . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`serverIndex` int(11) NOT NULL,
				`playerId` int(11) NOT NULL,
				`dataId` int(11) NOT NULL,
				`value` varchar(150) NOT NULL,
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `unique` (`dataId`,`playerId`,`serverIndex`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Player Data' AUTO_INCREMENT=1;";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error, E_USER_ERROR);
			return false;
		}
		$statement->close();
		return true;
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
} 
<?php

namespace ManiaControl\Players;


use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Utils\ClassUtil;

/**
 * Player Data Manager
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerDataManager implements UsageInformationAble {
	use UsageInformationTrait;
	
	/*
	 * Constants
	 */
	const TABLE_PLAYERDATAMETADATA = 'mc_playerdata_metadata';
	const TABLE_PLAYERDATA         = 'mc_playerdata';
	const TYPE_STRING              = 'string';
	const TYPE_INT                 = 'int';
	const TYPE_REAL                = 'real';
	const TYPE_BOOL                = 'bool';
	const TYPE_ARRAY               = 'array';
	const ARRAY_DELIMITER          = ';;';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $metaData     = array();
	private $storedData   = array();

	/**
	 * Construct a new player manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		// Store Stats MetaData
		$this->storeMetaData();
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli      = $this->maniaControl->getDatabase()->getMysqli();
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
	 * Store Meta Data from the Database in the Ram
	 */
	private function storeMetaData() {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();

		$query  = "SELECT * FROM `" . self::TABLE_PLAYERDATAMETADATA . "`;";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return;
		}

		while ($row = $result->fetch_object()) {
			$this->metaData[$row->class . $row->dataName] = $row;
		}
		$result->free();
	}

	/**
	 * Destroys the stored PlayerData (Method get called by PlayerManager, so don't call it anywhere else)
	 *
	 * @param Player $player
	 */
	public function destroyPlayerData(Player $player) {
		unset($this->storedData[$player->index]);
	}

	/**
	 * Defines the Player-Data MetaData
	 *
	 * @param mixed  $object
	 * @param string $dataName
	 * @param mixed  $default
	 * @param string $dataDescription (optional)
	 * @return bool
	 */
	public function defineMetaData($object, $dataName, $default, $dataDescription = '') {
		$mysqli    = $this->maniaControl->getDatabase()->getMysqli();
		$className = ClassUtil::getClass($object);

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
	 * Get Type of a Parameter
	 *
	 * @param mixed $param
	 * @return string
	 */
	private function getType($param) {
		if (is_int($param)) {
			return self::TYPE_INT;
		}
		if (is_float($param)) {
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
		trigger_error('Unsupported data type. ' . print_r($param, true));
		return null;
	}

	/**
	 * Gets the Player Data
	 *
	 * @param mixed  $object
	 * @param string $dataName
	 * @param Player $player
	 * @param int    $serverIndex
	 * @return mixed
	 */
	public function getPlayerData($object, $dataName, Player $player, $serverIndex = -1) {
		$className = ClassUtil::getClass($object);

		$meta = $this->metaData[$className . $dataName];

		// Check if data is already in the ram
		if (isset($this->storedData[$player->index]) && isset($this->storedData[$player->index][$meta->dataId])) {
			return $this->storedData[$player->index][$meta->dataId];
		}

		$mysqli        = $this->maniaControl->getDatabase()->getMysqli();
		$dataQuery     = "SELECT `value` FROM `" . self::TABLE_PLAYERDATA . "`
				WHERE `dataId` = ?
				AND `playerId` = ?
				AND `serverIndex` = ?;";
		$dataStatement = $mysqli->prepare($dataQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		$dataStatement->bind_param('iii', $meta->dataId, $player->index, $serverIndex);
		$dataStatement->execute();
		if ($dataStatement->error) {
			trigger_error($dataStatement->error);
			return null;
		}
		$dataStatement->store_result();
		if ($dataStatement->num_rows <= 0) {
			$this->setPlayerData($object, $dataName, $player, $meta->defaultValue, $serverIndex);
			return $meta->defaultValue;
		}
		$dataStatement->bind_result($value);
		$dataStatement->fetch();
		$dataStatement->free_result();
		$dataStatement->close();
		$data = $this->castSetting($meta->type, $value);

		// Store setting in the ram
		if (!isset($this->storedData[$player->index])) {
			$this->storedData[$player->index] = array();
		}
		$this->storedData[$player->index][$meta->dataId] = $data;

		return $data;
	}

	/**
	 * Set a PlayerData to a specific defined statMetaData
	 *
	 * @param mixed  $object
	 * @param string $dataName
	 * @param Player $player
	 * @param mixed  $value
	 * @param int    $serverIndex (empty if it's global)
	 * @return bool
	 */
	public function setPlayerData($object, $dataName, Player $player, $value, $serverIndex = -1) {
		$className = ClassUtil::getClass($object);
		if (!$player) {
			return false;
		}

		$dataId = $this->getMetaDataId($className, $dataName);
		if (!$dataId) {
			return false;
		}

		$mysqli    = $this->maniaControl->getDatabase()->getMysqli();
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

		// Store changed value
		if (!isset($this->storedData[$player->index])) {
			$this->storedData[$player->index] = array();
		}
		$this->storedData[$player->index][$dataId] = $value;

		return true;
	}

	/**
	 * Return the Id of the MetaData
	 *
	 * @param string $className
	 * @param string $statName
	 * @return int
	 */
	private function getMetaDataId($className, $statName) {
		if (isset($this->metaData[$className . $statName])) {
			$stat = $this->metaData[$className . $statName];
			return (int) $stat->dataId;
		}
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
			return (int) $value;
		}
		if ($type === self::TYPE_REAL) {
			return (float) $value;
		}
		if ($type === self::TYPE_BOOL) {
			return (bool) $value;
		}
		if ($type === self::TYPE_STRING) {
			return (string) $value;
		}
		if ($type === self::TYPE_ARRAY) {
			return explode(self::ARRAY_DELIMITER, $value);
		}
		trigger_error('Unsupported data type. ' . print_r($type, true));
		return $value;
	}
}

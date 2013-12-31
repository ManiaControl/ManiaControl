<?php
use ManiaControl\ManiaControl;

/**
 * Statistic Manager Class
 *
 * @author steeffeen & kremsy
 */
class StatisticManager {
	/**
	 * Constants
	 */
	const TABLE_STATMETADATA = 'mc_statmetadata';
	const TABLE_STATISTICS   = 'mc_statistics';

	const STAT_TYPE_INT = '0';

	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $mysqli = null;
	private $stats = array();

	/**
	 * Construct player manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->mysqli       = $this->maniaControl->database->mysqli;
		$this->initTables();

		//Store Stats MetaData
		$this->storeStatMetaData();
	}

	/**
	 * Get the value of an statistic
	 *
	 * @param      $statName
	 * @param      $playerId
	 * @param bool $serverLogin
	 * @return int
	 */
	public function getStatisticData($statName, $playerId, $serverLogin = false) {
		$statId = $this->getStatId($statName);

		if($statId == null) {
			return -1;
		}

		if(!$serverLogin) {
			$query = "SELECT SUM(value) as value FROM `" . self::TABLE_STATISTICS . "` WHERE `statId` = " . $statId . " AND `playerId` = " . $playerId . ";";
		} else {
			$query = "SELECT value FROM `" . self::TABLE_STATISTICS . "` WHERE `statId` = " . $statId . " AND `playerId` = " . $playerId . " AND `serverLogin` = '" . $serverLogin . "';";
		}

		$result = $this->mysqli->query($query);
		if(!$result) {
			trigger_error($this->mysqli->error);
		}

		$row = $result->fetch_object();

		$result->close();
		return $row->value;
	}

	/**
	 * Store Stats Meta Data from the Database
	 */
	private function storeStatMetaData() {
		$query  = "SELECT * FROM `" . self::TABLE_STATMETADATA . "`;";
		$result = $this->mysqli->query($query);
		if(!$result) {
			trigger_error($this->mysqli->error);
		}

		while($row = $result->fetch_object()) {
			$this->stats[$row->name] = $row;
		}

		$result->close();
	}

	/**
	 *    Returns the Stats Id
	 *
	 * @param $statName
	 * @return int
	 */
	private function getStatId($statName) {
		if(isset($this->stats[$statName])) {
			$stat = $this->stats[$statName];
			return $stat->index;
		} else {
			return null;
		}
	}

	/**
	 * Inserts a Stat into the database
	 *
	 * @param        $statName
	 * @param        $playerId
	 * @param string $serverLogin
	 * @param        $value , value to Add
	 * @param string $statType
	 */
	public function insertStat($statName, $playerId, $serverLogin, $value, $statType = self::STAT_TYPE_INT) {
		$statId = $this->getStatId($statName);

		if($statId == null) {
			return -1;
		}

		$query = "INSERT INTO `" . self::TABLE_STATISTICS . "` (
					`serverLogin`,
					`playerId`,
					`statId`,
					`value`
				) VALUES (
				?, ?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`value` = `value` + VALUES(`value`);";

		var_dump($query);
		$statement = $this->mysqli->prepare($query);
		if($this->mysqli->error) {
			trigger_error($this->mysqli->error);
			return false;
		}
		$statement->bind_param('siii', $serverLogin, $playerId, $statId, $value);
		$statement->execute();
		if($statement->error) {
			trigger_error($statement->error);
			$statement->close();
			return false;
		}

		$statement->close();
	}

	/**
	 * Increments a Statistic by one
	 *
	 * @param $statName
	 * @param $playerId
	 * @param $serverLogin
	 */
	public function incrementStat($statName, $playerId, $serverLogin) {
		$this->insertStat($statName, $playerId, $serverLogin, 1);
	}

	/**
	 * Defines a Stat
	 *
	 * @param string $statName
	 * @param string $statDescription
	 */
	public function defineStatMetaData($statName, $statDescription = '') {
		$query     = "INSERT IGNORE INTO `" . self::TABLE_STATMETADATA . "` (
					`name`,
					`description`
				) VALUES (
					?, ?
				);";
		$statement = $this->mysqli->prepare($query);
		if($this->mysqli->error) {
			trigger_error($this->mysqli->error);
			return false;
		}
		$statement->bind_param('ss', $statName, $statDescription);
		$statement->execute();
		if($statement->error) {
			trigger_error($statement->error);
			$statement->close();
			return false;
		}

		$statement->close();
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_STATMETADATA . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`description` varchar(150) COLLATE utf8_unicode_ci,
				PRIMARY KEY (`index`),
				UNIQUE KEY `name` (`name`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Statistics Meta Data' AUTO_INCREMENT=1;";

		$statement = $this->mysqli->prepare($query);
		if($this->mysqli->error) {
			trigger_error($this->mysqli->error, E_USER_ERROR);

			return false;
		}
		$statement->execute();
		if($statement->error) {
			trigger_error($statement->error, E_USER_ERROR);

			return false;
		}
		$statement->close();

		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_STATISTICS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`serverLogin` varchar(50) NOT NULL,
				`playerId` int(11) NOT NULL,
				`statId` int(11) NOT NULL,
				`value` int(20) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`index`),
				UNIQUE KEY `unique` (`statId`,`playerId`,`serverLogin`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Statistics' AUTO_INCREMENT=1;";

		$statement = $this->mysqli->prepare($query);
		if($this->mysqli->error) {
			trigger_error($this->mysqli->error, E_USER_ERROR);

			return false;
		}
		$statement->execute();
		if($statement->error) {
			trigger_error($statement->error, E_USER_ERROR);

			return false;
		}
		$statement->close();
	}
} 
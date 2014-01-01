<?php

namespace ManiaControl\Statistics;

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
	const TABLE_STATISTICS = 'mc_statistics';
	
	/**
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Construct player manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();
	}

	/**
	 * Defines a Stat
	 *
	 * @param string $statName
	 * @param string $statDescription
	 * @return bool
	 */
	public function defineStatMetaData($statName, $statDescription = '') {
		$mysqli = $this->maniaControl->database->mysqli;
		$query = "INSERT IGNORE INTO `" . self::TABLE_STATMETADATA . "` (
				`name`,
				`description`
				) VALUES (
				?, ?
				);";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$statement->bind_param('ss', $statName, $statDescription);
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
	 * Initialize necessary Database Tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_STATMETADATA . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`description` varchar(150) COLLATE utf8_unicode_ci,
				PRIMARY KEY (`index`),
				UNIQUE KEY `name` (`name`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Statistics Meta Data' AUTO_INCREMENT=1;";
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
		
		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_STATISTICS . "` (
				`statId` int(11) NOT NULL AUTO_INCREMENT,
				`playerId` int(11) NOT NULL,
				`serverId` int(11) NOT NULL,
				`value` int(20) COLLATE utf8_unicode_ci NOT NULL,
				UNIQUE KEY `unique` (`statId`,`playerId`,`serverId`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Statistics Data' AUTO_INCREMENT=1;";
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
} 

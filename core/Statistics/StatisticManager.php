<?php

namespace ManiaControl\Statistics;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Statistic Manager Class
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class StatisticManager implements UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const TABLE_STATMETADATA = 'mc_statmetadata';
	const TABLE_STATISTICS   = 'mc_statistics';
	const STAT_TYPE_INT      = '0';
	const STAT_TYPE_TIME     = '1';
	const STAT_TYPE_FLOAT    = '2';

	const SPECIAL_STAT_KD_RATIO    = 'Kill Death Ratio'; //TODO dynamic later
	const SPECIAL_STAT_HITS_PH     = 'Hits Per Hour';
	const SPECIAL_STAT_LASER_ACC   = 'Laser Accuracy';
	const SPECIAL_STAT_NUCLEUS_ACC = 'Nucleus Accuracy';
	const SPECIAL_STAT_ROCKET_ACC  = 'Rocket Accuracy';
	const SPECIAL_STAT_ARROW_ACC   = 'Arrow Accuracy';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $stats        = array();
	private $specialStats = array();

	/** @var StatisticCollector $statisticCollector */
	private $statisticCollector = null;

	/** @var SimpleStatsList $simpleStatsList */
	private $simpleStatsList = null;

	/**
	 * Construct a new statistic manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		$this->statisticCollector = new StatisticCollector($maniaControl);
		$this->simpleStatsList    = new SimpleStatsList($maniaControl);

		// Store Stats MetaData
		$this->storeStatMetaData();
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli    = $this->maniaControl->getDatabase()->getMysqli();
		$query     = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_STATMETADATA . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(100) NOT NULL,
				`type` int(5) NOT NULL,
				`description` varchar(150) NOT NULL,
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

		$query     = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_STATISTICS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`serverIndex` int(11) NOT NULL,
				`playerId` int(11) NOT NULL,
				`statId` int(11) NOT NULL,
				`value` int(20) NOT NULL DEFAULT '0',
				PRIMARY KEY (`index`),
				UNIQUE KEY `unique` (`statId`,`playerId`,`serverIndex`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Statistics' AUTO_INCREMENT=1;";
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
	 * Store Stats Meta Data from the Database
	 */
	private function storeStatMetaData() {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();

		$query  = "SELECT * FROM `" . self::TABLE_STATMETADATA . "`;";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return;
		}

		while ($row = $result->fetch_object()) {
			$this->stats[$row->name] = $row;
		}
		$result->free();

		// TODO: own model class

		//Define Special Stat Kill / Death Ratio
		$stat                                            = new \stdClass();
		$stat->name                                      = self::SPECIAL_STAT_KD_RATIO;
		$stat->type                                      = self::STAT_TYPE_FLOAT;
		$this->specialStats[self::SPECIAL_STAT_KD_RATIO] = $stat;

		//Hits Per Hour
		$stat                                           = new \stdClass();
		$stat->name                                     = self::SPECIAL_STAT_HITS_PH;
		$stat->type                                     = self::STAT_TYPE_FLOAT;
		$this->specialStats[self::SPECIAL_STAT_HITS_PH] = $stat;

		//Laser Accuracy
		$stat                                             = new \stdClass();
		$stat->name                                       = self::SPECIAL_STAT_LASER_ACC;
		$stat->type                                       = self::STAT_TYPE_FLOAT;
		$this->specialStats[self::SPECIAL_STAT_LASER_ACC] = $stat;

		//Nucleus Accuracy
		$stat                                               = new \stdClass();
		$stat->name                                         = self::SPECIAL_STAT_NUCLEUS_ACC;
		$stat->type                                         = self::STAT_TYPE_FLOAT;
		$this->specialStats[self::SPECIAL_STAT_NUCLEUS_ACC] = $stat;

		//Arrow Accuracy
		$stat                                             = new \stdClass();
		$stat->name                                       = self::SPECIAL_STAT_ARROW_ACC;
		$stat->type                                       = self::STAT_TYPE_FLOAT;
		$this->specialStats[self::SPECIAL_STAT_ARROW_ACC] = $stat;

		//Rocket Accuracy
		$stat                                              = new \stdClass();
		$stat->name                                        = self::SPECIAL_STAT_ROCKET_ACC;
		$stat->type                                        = self::STAT_TYPE_FLOAT;
		$this->specialStats[self::SPECIAL_STAT_ROCKET_ACC] = $stat;
	}

	/**
	 * Return the statistic collector
	 *
	 * @api
	 * @return StatisticCollector
	 */
	public function getStatisticCollector() {
		return $this->statisticCollector;
	}

	/**
	 * Return the simple stats list
	 *
	 * @api
	 * @return SimpleStatsList
	 */
	public function getSimpleStatsList() {
		return $this->simpleStatsList;
	}

	/**
	 * Get All statistics ordered by an given name
	 *
	 * @api
	 * @param string $statName
	 * @param        $serverIndex
	 * @param        $minValue
	 * @param        $limit
	 * @internal param $orderedBy
	 * @return array
	 */
	public function getStatsRanking($statName = '', $serverIndex = -1, $minValue = -1, $limit = 200) {
		if (isset($this->specialStats[$statName])) {
			return $this->getStatsRankingOfSpecialStat($statName, $serverIndex, $limit);
		}

		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$statId = $this->getStatId($statName);

		$query = "SELECT `playerId`, `serverIndex`, `value` FROM `" . self::TABLE_STATISTICS . "`
				WHERE `statId` = {$statId} ";
		if ($minValue >= 0) {
			$query .= "AND `value` >= {$minValue} ";
		}
		$query .= "ORDER BY `value` DESC";

		if ($limit > 0) {
			$query .= " LIMIT " . $limit;
		}

		$query .= ";";

		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return null;
		}

		$stats = array();
		while ($row = $result->fetch_object()) {
			if ($serverIndex < 0) {
				if (!isset($stats[$row->playerId])) {
					$stats[$row->playerId] = $row->value;
				} else {
					$stats[$row->playerId] += $row->value;
				}
			} else if ($serverIndex == $row->serverIndex) {
				$stats[$row->playerId] = $row->value;
			}
		}
		$result->free();

		arsort($stats);
		return $stats;
	}

	/**
	 * Gets The Ranking of an Special Stat
	 *
	 * @api
	 * @param string $statName
	 * @param int    $serverIndex
	 * @param int    $limit
	 * @return array
	 */
	public function getStatsRankingOfSpecialStat($statName = '', $serverIndex = -1, $limit = 200) {
		$statsArray = array();
		switch ($statName) {
			case self::SPECIAL_STAT_KD_RATIO:
				$kills  = $this->getStatsRanking(StatisticCollector::STAT_ON_KILL, $serverIndex, $limit);
				$deaths = $this->getStatsRanking(StatisticCollector::STAT_ON_DEATH, $serverIndex, $limit);
				if (!$kills || !$deaths) {
					return array();
				}
				foreach ($deaths as $key => $death) {
					if (!$death || !isset($kills[$key])) {
						continue;
					}
					$statsArray[$key] = intval($kills[$key]) / intval($death);
				}
				arsort($statsArray);
				break;
			case self::SPECIAL_STAT_HITS_PH:
				$hits  = $this->getStatsRanking(StatisticCollector::STAT_ON_HIT, $serverIndex, $limit);
				$times = $this->getStatsRanking(StatisticCollector::STAT_PLAYTIME, $serverIndex, $limit);
				if (!$hits || !$times) {
					return array();
				}
				foreach ($times as $key => $time) {
					if (!$time || !isset($hits[$key])) {
                        continue;
					}
					$statsArray[$key] = intval($hits[$key]) / (intval($time) / 3600);
				}
				arsort($statsArray);
				break;
			case self::SPECIAL_STAT_ARROW_ACC:
				$hits  = $this->getStatsRanking(StatisticCollector::STAT_ARROW_HIT, $serverIndex, $limit);
				$shots = $this->getStatsRanking(StatisticCollector::STAT_ARROW_SHOT, $serverIndex, $limit);
				if (!$hits || !$shots) {
					return array();
				}
				foreach ($shots as $key => $shot) {
					if (!$shot || !isset($hits[$key])) {
                        continue;
					}
					$statsArray[$key] = intval($hits[$key]) / (intval($shot));
				}
				arsort($statsArray);
				break;
			case self::SPECIAL_STAT_LASER_ACC:
				$hits  = $this->getStatsRanking(StatisticCollector::STAT_LASER_HIT, $serverIndex, $limit);
				$shots = $this->getStatsRanking(StatisticCollector::STAT_LASER_SHOT, $serverIndex, $limit);
				if (!$hits || !$shots) {
					return array();
				}
				foreach ($shots as $key => $shot) {
					if (!$shot || !isset($hits[$key])) {
                        continue;
					}
					$statsArray[$key] = intval($hits[$key]) / (intval($shot));
				}
				arsort($statsArray);
				break;
			case self::SPECIAL_STAT_ROCKET_ACC:
				$hits  = $this->getStatsRanking(StatisticCollector::STAT_ROCKET_HIT, $serverIndex, $limit);
				$shots = $this->getStatsRanking(StatisticCollector::STAT_ROCKET_SHOT, $serverIndex, $limit);
				if (!$hits || !$shots) {
					return array();
				}
				foreach ($shots as $key => $shot) {
					if (!$shot || !isset($hits[$key])) {
                        continue;
					}
					$statsArray[$key] = intval($hits[$key]) / (intval($shot));
				}
				arsort($statsArray);
				break;
			case self::SPECIAL_STAT_NUCLEUS_ACC:
				$hits  = $this->getStatsRanking(StatisticCollector::STAT_NUCLEUS_HIT, $serverIndex, $limit);
				$shots = $this->getStatsRanking(StatisticCollector::STAT_NUCLEUS_SHOT, $serverIndex, $limit);
				if (!$hits || !$shots) {
					return array();
				}
				foreach ($shots as $key => $shot) {
					if (!$shot || !isset($hits[$key])) {
                        continue;
					}
					$statsArray[$key] = intval($hits[$key]) / (intval($shot));
				}
				arsort($statsArray);
				break;
		}
		return $statsArray;
	}

	/**
	 * Returns the total amount of players who were on the server once
	 *
	 * @param $serverIndex
	 * @return int
	 */
	public function getTotalStatsPlayerCount($serverIndex){
		return count($this->getStatsRanking(PlayerManager::STAT_SERVERTIME,$serverIndex,-1,20000));
	}

	/**
	 * Return the Stat Id
	 *
	 * @api
	 * @param string $statName
	 * @return int
	 */
	private function getStatId($statName) {
		if (isset($this->stats[$statName])) {
			$stat = $this->stats[$statName];
			return (int) $stat->index;
		}
		return null;
	}

	/**
	 * Get all statistics of a certain player
	 *
	 * @api
	 * @param Player $player
	 * @param int    $serverIndex
	 * @return array
	 */
	public function getAllPlayerStats(Player $player, $serverIndex = -1) {
		$playerStats = array();
		foreach ($this->stats as $stat) {
			$value                    = $this->getStatisticData($stat->name, $player->index, $serverIndex);
			$playerStats[$stat->name] = array($stat, $value);
		}

		foreach ($this->specialStats as $stat) {
			switch ($stat->name) {
				case self::SPECIAL_STAT_KD_RATIO:
					if (!isset($playerStats[StatisticCollector::STAT_ON_KILL]) || !isset($playerStats[StatisticCollector::STAT_ON_DEATH])) {
						break;
					}
					$kills  = intval($playerStats[StatisticCollector::STAT_ON_KILL][1]);
					$deaths = intval($playerStats[StatisticCollector::STAT_ON_DEATH][1]);
					if (!$deaths) {
                        break;
					}
					$playerStats[$stat->name] = array($stat, $kills / $deaths);
					break;
				case self::SPECIAL_STAT_HITS_PH:
					if (!isset($playerStats[StatisticCollector::STAT_PLAYTIME]) || !isset($playerStats[StatisticCollector::STAT_ON_HIT])) {
                        break;
					}
					$hits = intval($playerStats[StatisticCollector::STAT_ON_HIT][1]);
					$time = intval($playerStats[StatisticCollector::STAT_PLAYTIME][1]);
					if (!$time) {
                        break;
					}
					$playerStats[$stat->name] = array($stat, $hits / ($time / 3600));
					break;
				case self::SPECIAL_STAT_ARROW_ACC:
					if (!isset($playerStats[StatisticCollector::STAT_ARROW_HIT]) || !isset($playerStats[StatisticCollector::STAT_ARROW_SHOT])) {
                        break;
					}
					$hits  = intval($playerStats[StatisticCollector::STAT_ARROW_HIT][1]);
					$shots = intval($playerStats[StatisticCollector::STAT_ARROW_SHOT][1]);
					if (!$shots) {
                        break;
					}
					$playerStats[$stat->name] = array($stat, $hits / $shots);
					break;
				case self::SPECIAL_STAT_LASER_ACC:
					if (!isset($playerStats[StatisticCollector::STAT_LASER_HIT]) || !isset($playerStats[StatisticCollector::STAT_LASER_SHOT])) {
                        break;
					}
					$hits  = intval($playerStats[StatisticCollector::STAT_LASER_HIT][1]);
					$shots = intval($playerStats[StatisticCollector::STAT_LASER_SHOT][1]);
					if (!$shots) {
                        break;
					}
					$playerStats[$stat->name] = array($stat, $hits / $shots);
					break;
				case self::SPECIAL_STAT_ROCKET_ACC:
					if (!isset($playerStats[StatisticCollector::STAT_ROCKET_HIT]) || !isset($playerStats[StatisticCollector::STAT_ROCKET_SHOT])) {
                        break;
					}
					$hits  = intval($playerStats[StatisticCollector::STAT_ROCKET_HIT][1]);
					$shots = intval($playerStats[StatisticCollector::STAT_ROCKET_SHOT][1]);
					if (!$shots) {
                        break;
					}
					$playerStats[$stat->name] = array($stat, $hits / $shots);
					break;
				case self::SPECIAL_STAT_NUCLEUS_ACC:
					if (!isset($playerStats[StatisticCollector::STAT_NUCLEUS_HIT]) || !isset($playerStats[StatisticCollector::STAT_NUCLEUS_SHOT])) {
                        break;
					}
					$hits  = intval($playerStats[StatisticCollector::STAT_NUCLEUS_HIT][1]);
					$shots = intval($playerStats[StatisticCollector::STAT_NUCLEUS_SHOT][1]);
					if (!$shots) {
                        break;
					}
					$playerStats[$stat->name] = array($stat, (float) ($hits / $shots));
					break;
			}
		}
		return $playerStats;
	}

	/**
	 * Get the value of an statistic
	 *
	 * @api
	 * @param     $statName
	 * @param     $playerId
	 * @param int $serverIndex
	 * @return int
	 */
	public function getStatisticData($statName, $playerId, $serverIndex = -1) {
		//Handle Special Stats
		switch ($statName) {
			case self::SPECIAL_STAT_KD_RATIO:
				$kills  = $this->getStatisticData(StatisticCollector::STAT_ON_KILL, $playerId, $serverIndex);
				$deaths = $this->getStatisticData(StatisticCollector::STAT_ON_DEATH, $playerId, $serverIndex);
				if (!$deaths) {
					return -1;
				}
				return intval($kills) / intval($deaths);
			case self::SPECIAL_STAT_HITS_PH:
				$hits = $this->getStatisticData(StatisticCollector::STAT_ON_HIT, $playerId, $serverIndex);
				$time = $this->getStatisticData(StatisticCollector::STAT_PLAYTIME, $playerId, $serverIndex);
				if (!$time) {
					return -1;
				}
				return intval($hits) / (intval($time) / 3600);
			case self::SPECIAL_STAT_ARROW_ACC:
				$hits  = $this->getStatisticData(StatisticCollector::STAT_ARROW_HIT, $playerId, $serverIndex);
				$shots = $this->getStatisticData(StatisticCollector::STAT_ARROW_SHOT, $playerId, $serverIndex);
				if (!$shots) {
					return -1;
				}
				return intval($hits) / intval($shots);
			case self::SPECIAL_STAT_LASER_ACC:
				$hits  = $this->getStatisticData(StatisticCollector::STAT_LASER_HIT, $playerId, $serverIndex);
				$shots = $this->getStatisticData(StatisticCollector::STAT_LASER_SHOT, $playerId, $serverIndex);
				if (!$shots) {
					return -1;
				}
				return intval($hits) / intval($shots);
			case self::SPECIAL_STAT_NUCLEUS_ACC:
				$hits  = $this->getStatisticData(StatisticCollector::STAT_NUCLEUS_HIT, $playerId, $serverIndex);
				$shots = $this->getStatisticData(StatisticCollector::STAT_NUCLEUS_SHOT, $playerId, $serverIndex);
				if (!$shots) {
					return -1;
				}
				return intval($hits) / intval($shots);
			case self::SPECIAL_STAT_ROCKET_ACC:
				$hits  = $this->getStatisticData(StatisticCollector::STAT_ROCKET_HIT, $playerId, $serverIndex);
				$shots = $this->getStatisticData(StatisticCollector::STAT_ROCKET_SHOT, $playerId, $serverIndex);
				if (!$shots) {
					return -1;
				}
				return intval($hits) / intval($shots);
		}

		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$statId = $this->getStatId($statName);

		if (!$statId) {
			return -1;
		}

		if ($serverIndex < 0) {
			$query = "SELECT SUM(`value`) as `value` FROM `" . self::TABLE_STATISTICS . "`
					WHERE `statId` = {$statId}
					AND `playerId` = {$playerId};";
		} else {
			$query = "SELECT `value` FROM `" . self::TABLE_STATISTICS . "`
					WHERE `statId` = {$statId}
					AND `playerId` = {$playerId}
					AND `serverIndex` = {$serverIndex};";
		}

		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return -1;
		}

		$row = $result->fetch_object();

		$result->free();
		return $row->value;
	}

	/**
	 * Increments a Statistic by one
	 *
	 * @api
	 * @param string $statName
	 * @param Player $player
	 * @param int    $serverIndex
	 * @return bool
	 */
	public function incrementStat($statName, Player $player, $serverIndex = -1) {
		return $this->insertStat($statName, $player, $serverIndex, 1);
	}

	/**
	 * Inserts a Stat into the database
	 *
	 * @api
	 * @param string $statName
	 * @param Player $player
	 * @param int    $serverIndex
	 * @param mixed  $value , value to Add
	 * @param string $statType
	 * @return bool
	 */
	public function insertStat($statName, Player $player, $serverIndex = -1, $value, $statType = self::STAT_TYPE_INT) {
		// TODO: statType isn't used
		if (!$player) {
			return false;
		}
		if ($player->isFakePlayer()) {
			return true;
		}
		$statId = $this->getStatId($statName);
		if (!$statId) {
			return false;
		}
		if ($value < 1) {
			return false;
		}

		if ($serverIndex) {
			$serverIndex = $this->maniaControl->getServer()->index;
		}

		$mysqli    = $this->maniaControl->getDatabase()->getMysqli();
		$query     = "INSERT INTO `" . self::TABLE_STATISTICS . "` (
				`serverIndex`,
				`playerId`,
				`statId`,
				`value`
				) VALUES (
				?, ?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`value` = `value` + VALUES(`value`);";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$statement->bind_param('iiii', $serverIndex, $player->index, $statId, $value);
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
	 * Defines a Stat
	 *
	 * @api
	 * @param        $statName
	 * @param string $type
	 * @param string $statDescription
	 * @return bool
	 */
	public function defineStatMetaData($statName, $type = self::STAT_TYPE_INT, $statDescription = '') {
		$mysqli    = $this->maniaControl->getDatabase()->getMysqli();
		$query     = "INSERT INTO `" . self::TABLE_STATMETADATA . "` (
				`name`,
				`type`,
				`description`
				) VALUES (
				?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`type` = VALUES(`type`),
				`description` = VALUES(`description`);";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$statement->bind_param('sis', $statName, $type, $statDescription);
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error);
			$statement->close();
			return false;
		}
		$statement->close();
		return true;
	}
}

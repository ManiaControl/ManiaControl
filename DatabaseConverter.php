<?php
/**
 * MPAseco to ManiaControl Database Converter
 * 2014 by Lukas Kremsmayr
 **/

$host   = "localhost";
$port   = 3306;

$targetUser = "smparagon";
$targetDb = "maniacontrol_1337";
$targetPass = "";

$user   = "smesc1";
$dbname = "smesc2";
$pass   = "";


$converter = new DatabaseConverter($host, $port, $targetUser, $targetPass, $targetDb);
$converter->connectToSourceDB($host, $port, $user, $pass, $dbname);
$test1 = $converter->convertPlayersAndStatistics();
$test2 = $converter->convertMapsAndKarma();
unset($converter);
var_dump($test1 && $test2);

class DatabaseConverter {
	/**
	 * Constants
	 */
	const MC_TABLE_STATISTICS    = 'mc_statistics';
	const MC_TABLE_STATSMETADATA = 'mc_statmetadata';
	const MC_TABLE_PLAYERS       = 'mc_players';
	const MC_TABLE_MAPS          = 'mc_maps';
	const MC_TABLE_KARMA         = 'mc_karma';
	const AS_TABLE_MAPS          = 'maps';
	const AS_TABLE_PLAYERS       = 'players';
	const AS_TABLE_PLAYERS_EXTRA = 'players_extra';
	const AS_TABLE_KARMA         = 'rs_karma';

	/**
	 * Public properties
	 */
	private $mysqli = null;
	/** @var \mysqli $sourceMysqli */
	private $sourceMysqli = null;

	private $targetDatabase = "";


	public function __construct($host, $port, $user, $pass, $dbname) {
		$host = (string)$host;
		$port = (int)$port;
		$user = (string)$user;
		$pass = (string)$pass;

		$this->targetDatabase = $dbname;

		// Open database connection
		$this->mysqli = new \mysqli($host, $user, $pass, null, $port);
		if ($this->mysqli->connect_error) {
			trigger_error($this->mysqli->connect_error, E_USER_ERROR);
		}
		$this->mysqli->set_charset("utf8");

		$this->initDatabase();
	}

	public function connectToSourceDB($host, $port, $user, $pass, $dbname) {
		$host   = (string)$host;
		$port   = (int)$port;
		$user   = (string)$user;
		$pass   = (string)$pass;
		$dbname = (string)$dbname;

		// Open database connection
		$this->sourceMysqli = new \mysqli($host, $user, $pass, null, $port);
		if ($this->sourceMysqli->connect_error) {
			trigger_error($this->sourceMysqli->connect_error, E_USER_ERROR);
		}
		$this->sourceMysqli->set_charset("utf8");

		// Connect to new database
		$this->sourceMysqli->select_db($dbname);
		if ($this->sourceMysqli->error) {
			trigger_error("Couldn't select database '{$dbname}'. " . $this->sourceMysqli->error, E_USER_ERROR);
			return false;
		}
		return true;
	}

	/**
	 * Destruct database connection
	 */
	public function __destruct() {
		$this->mysqli->close();
		if ($this->sourceMysqli != null) {
			$this->sourceMysqli->close();
		}
	}

	public function convertMapsAndKarma() {
		$success = $this->initMapsTable();
		if (!$success) {
			return false;
		}

		$mapQuery = "SELECT * FROM `" . self::AS_TABLE_MAPS . "`;";
		$result   = $this->sourceMysqli->query($mapQuery);

		if (!$result) {
			return false;
		}

		$mapConvertQuery = "INSERT IGNORE INTO `" . self::MC_TABLE_MAPS . "`
			( `uid`, `name`, `authorLogin`, `environment`)
			VALUES
			( ?,?,?, ? );";
		$statement       = $this->mysqli->prepare($mapConvertQuery);
		if ($this->mysqli->error) {
			trigger_error($this->mysqli->error);
			return false;
		}


		//Loop through all the players
		while($row = $result->fetch_object()) {
			$statement->bind_param('ssss', $row->Uid, $row->Name, $row->Author, $row->Environment);
			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
				return false;
			}
		}
		$result->free_result();
		$statement->close();

		return $this->convertKarma();
	}

	private function convertKarma() {
		$success = $this->initKarmaTable();
		if (!$success) {
			return false;
		}

		//Build Map Vector
		$mapQuery = "SELECT uid, " . self::MC_TABLE_MAPS . ".index FROM `" . self::MC_TABLE_MAPS . "`;";
		$result   = $this->mysqli->query($mapQuery);
		if (!$result) {
			var_dump($this->mysqli->error);
			trigger_error("Building Map Vector failed");
			return false;
		}


		$mapvector = array();
		while($row = $result->fetch_object()) {
			$mapvector[$row->uid] = $row->index;
		}
		$result->free_result();

		//Build Player Vector
		$playerQuery = "SELECT " . self::MC_TABLE_PLAYERS . ".index, login FROM `" . self::MC_TABLE_PLAYERS . "`;";
		$result      = $this->mysqli->query($playerQuery);

		if (!$result) {
			trigger_error("Building Player Vector failed");
			return false;
		}

		$playerVector = array();
		while($row = $result->fetch_object()) {
			$playerVector[$row->login] = $row->index;
		}
		$result->free_result();


		//SELECT Login, Uid, Score FROM `rs_karma` INNER JOIN `players` ON (players.Id = rs_karma.PlayerId) LEFT JOIN `maps` ON (maps.id = rs_karma.MapId)
		//Get Karma Table
		$karmaQuery = "SELECT Login, Uid, Score FROM `" . self::AS_TABLE_KARMA . "`
					LEFT JOIN `" . self::AS_TABLE_MAPS . "` ON (" . self::AS_TABLE_MAPS . ".id = " . self::AS_TABLE_KARMA . ".MapId)
					INNER JOIN `" . self::AS_TABLE_PLAYERS . "` ON (" . self::AS_TABLE_PLAYERS . ".Id = " . self::AS_TABLE_KARMA . ".PlayerId);";
		$result     = $this->sourceMysqli->query($karmaQuery);

		if (!$result) {
			return false;
		}

		$karmaConvertQuery = "INSERT IGNORE INTO `" . self::MC_TABLE_KARMA . "`
			( `mapIndex`, `playerIndex`, `vote`)
			VALUES
			( ?,?,?);";
		$statement         = $this->mysqli->prepare($karmaConvertQuery);
		if ($this->mysqli->error) {
			trigger_error($this->mysqli->error);
			return false;
		}

		//Loop through all the players
		while($row = $result->fetch_object()) {
			if (!isset($mapvector[$row->Uid])) {
				continue;
			}
			$mapId    = intval($mapvector[$row->Uid]);
			$playerId = intval($playerVector[$row->Login]);
			$vote     = 1;
			if ($row->Score == -1) {
				$vote = 0;
			}
			$statement->bind_param('iii', $mapId, $playerId, $vote);
			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
				return false;
			}
		}
		$result->free_result();
		$statement->close();

		return true;
	}

	public function convertPlayersAndStatistics($serverIndex = -1) {
		$success = $this->initPlayerTable();
		if (!$success) {
			return false;
		}

		$success = $this->initStatsTable();
		if (!$success) {
			return false;
		}

		if (!isset($this->sourceMysqli)) {
			return false;
		}

		$databaseQuery = "SELECT * FROM `" . self::AS_TABLE_PLAYERS . "`;";

		$result = $this->sourceMysqli->query($databaseQuery);

		if (!$result) {
			return false;
		}


		$playerConvertQuery = "INSERT INTO `" . self::MC_TABLE_PLAYERS . "`
			( `index`, `login`, `nickname`)
			VALUES
			( ?,?,? )
			ON DUPLICATE KEY UPDATE
			`index` = LAST_INSERT_ID(`index`);";
		$statement          = $this->mysqli->prepare($playerConvertQuery);
		if ($this->mysqli->error) {
			trigger_error($this->mysqli->error);
			return false;
		}

		$statisticQuery = "INSERT IGNORE INTO `" . self::MC_TABLE_STATISTICS . "`
			(`playerId`, `statId`, `value`, `serverIndex`)
			VALUES
			(?,?,?,?);";
		$statStatement  = $this->mysqli->prepare($statisticQuery);
		if ($this->mysqli->error) {
			trigger_error($this->mysqli->error);
			return false;
		}

		//Loop through all the players
		while($row = $result->fetch_object()) {
			$statement->bind_param('iss', $row->Id, $row->Login, $row->NickName);
			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
				return false;
			}


			if ($row->Joins > 0) {
				$statId = 0;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->Joins, $serverIndex);
				$statStatement->execute();
			}

			if ($row->TimePlayed > 0) {
				$statId = 2;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->TimePlayed, $serverIndex);
				$statStatement->execute();
			}

			if ($row->Survivals > 0) {
				$statId = 4;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->Survivals, $serverIndex);
				$statStatement->execute();
			}

			if ($row->Wins > 0) {
				$statId = 5;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->Wins, $serverIndex);
				$statStatement->execute();
			}

			if ($row->NearMisses > 0) {
				$statId = 6;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->NearMisses, $serverIndex);
				$statStatement->execute();
			}

			if ($row->Captures > 0) {
				$statId = 7;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->Captures, $serverIndex);
				$statStatement->execute();
			}

			if ($row->Hits > 0) {
				$statId = 8;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->Hits, $serverIndex);
				$statStatement->execute();
			}

			if ($row->GotHits > 0) {
				$statId = 9;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->GotHits, $serverIndex);
				$statStatement->execute();
			}

			if ($row->Deaths > 0) {
				$statId = 10;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->Deaths, $serverIndex);
				$statStatement->execute();
			}

			if ($row->Respawns > 0) {
				$statId = 11;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->Respawns, $serverIndex);
				$statStatement->execute();
			}

			if ($row->Shots > 0) {
				$statId = 13;
				$statStatement->bind_param('iiii', $row->Id, $statId, $row->Shots, $serverIndex);
				$statStatement->execute();
			}
			/*
					(12, 'Kills', 0, ''),
			 */
		}

		$statement->close();
		$statStatement->close();

		return $this->convertPlayersExtra($serverIndex);
	}

	private function convertPlayersExtra($serverIndex) { //TODO dont rely on playerId from Aseco table
		$databaseQuery = "SELECT * FROM `" . self::AS_TABLE_PLAYERS_EXTRA . "`;";

		$result = $this->sourceMysqli->query($databaseQuery);

		if (!$result) {
			return false;
		}

		$statisticQuery = "INSERT IGNORE INTO `" . self::MC_TABLE_STATISTICS . "`
			(`playerId`, `statId`, `value`, `serverIndex`)
			VALUES
			(?,?,?,?);";
		$statement      = $this->mysqli->prepare($statisticQuery);
		if ($this->mysqli->error) {
			trigger_error($this->mysqli->error);
			return false;
		}

		//Loop through all the players and Insert the Donations Stat
		while($row = $result->fetch_object()) {
			if ($row->Donations == 0) {
				continue;
			}

			$statId = 3;
			$statement->bind_param('iiii', $row->PlayerId, $statId, $row->Donations, $serverIndex);
			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
				return false;
			}

		}
		$statement->close();
		return true;
	}

	/**
	 * Connect to the defined database (create it if needed)
	 *
	 * @return bool
	 */
	private function initDatabase() {
		$dbName = (string)$this->targetDatabase;
		if (!$dbName) {
			trigger_error("Invalid database configuration (database).", E_USER_ERROR);
			return false;
		}

		// Try to connect
		$result = $this->mysqli->select_db($dbName);
		if ($result) {
			return true;
		}

		// Create database
		$databaseQuery = "CREATE DATABASE {$dbName};";
		$this->mysqli->query($databaseQuery);
		if ($this->mysqli->error) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return false;
		}

		// Connect to new database
		$this->mysqli->select_db($dbName);
		if ($this->mysqli->error) {
			trigger_error("Couldn't select database '{$dbName}'. " . $this->mysqli->error, E_USER_ERROR);
			return false;
		}
		return true;
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initPlayerTable() {
		$mysqli               = $this->mysqli;
		$playerTableQuery     = "CREATE TABLE IF NOT EXISTS `" . self::MC_TABLE_PLAYERS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`nickname` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
				`path` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`authLevel` int(11) NOT NULL DEFAULT '0',
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `login` (`login`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Player Data' AUTO_INCREMENT=1;";
		$playerTableStatement = $mysqli->prepare($playerTableQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$playerTableStatement->execute();
		if ($playerTableStatement->error) {
			trigger_error($playerTableStatement->error, E_USER_ERROR);
			return false;
		}
		$playerTableStatement->close();
		return true;
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initMapsTable() {
		$mysqli = $this->mysqli;
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::MC_TABLE_MAPS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`mxid` int(11),
				`uid` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`name` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
				`authorLogin` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`fileName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`environment` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`mapType` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `uid` (`uid`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Map data' AUTO_INCREMENT=1;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		return $result;
	}

	/**
	 * Initialize statistic Tables
	 *
	 * @return bool
	 */
	private function initStatsTable() {
		$mysqli    = $this->mysqli;
		$query     = "CREATE TABLE IF NOT EXISTS `" . self::MC_TABLE_STATSMETADATA . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`type` int(5) NOT NULL,
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

		$query     = "CREATE TABLE IF NOT EXISTS `" . self::MC_TABLE_STATISTICS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`serverIndex` int(11) NOT NULL,
				`playerId` int(11) NOT NULL,
				`statId` int(11) NOT NULL,
				`value` int(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
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

		$query = "INSERT IGNORE INTO `" . self::MC_TABLE_STATSMETADATA . "` (`index`, `name`, `type`, `description`) VALUES
					(1, 'Joins', 0, ''),
					(2, 'Servertime', 1, ''),
					(3, 'Donated Planets', 0, ''),
					(4, 'Survivals', 0, ''),
					(5, 'Wins', 0, ''),
					(6, 'Near Misses', 0, ''),
					(7, 'Captures', 0, ''),
					(8, 'Hits', 0, ''),
					(9, 'Got Hits', 0, ''),
					(10, 'Deaths', 0, ''),
					(11, 'Respawns', 0, ''),
					(12, 'Kills', 0, ''),
					(13, 'Shots', 0, '');";

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
	 * Create necessary database tables
	 */
	private function initKarmaTable() {
		$mysqli = $this->mysqli;
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::MC_TABLE_KARMA . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`mapIndex` int(11) NOT NULL,
				`playerIndex` int(11) NOT NULL,
				`vote` float NOT NULL DEFAULT '-1',
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `player_map_vote` (`mapIndex`, `playerIndex`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Save players map votes' AUTO_INCREMENT=1;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		return $result;
	}
}
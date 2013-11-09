<?php

namespace mControl;

/**
 * Class for database connection
 *
 * @author steeffeen
 */
class Database {
	/**
	 * Constants
	 */
	const TABLE_PLAYERS = 'ic_players';
	const TABLE_MAPS = 'ic_maps';

	/**
	 * Public properties
	 */
	public $mysqli = null;

	/**
	 * Private properties
	 */
	private $mControl = null;

	private $config = null;

	private $multiQueries = '';

	/**
	 * Construct database connection
	 */
	public function __construct($mControl) {
		$this->mControl = $mControl;
		
		// Load config
		$this->config = Tools::loadConfig('database.mControl.xml');
		$this->iControl->checkConfig($this->config, array("host", "user"), 'database.mControl.xml');
		
		// Get mysql server information
		$host = $this->config->xpath('host');
		if (!$host) trigger_error("Invalid database configuration (host).", E_USER_ERROR);
		$host = (string) $host[0];
		
		$port = $this->config->xpath('port');
		if (!$port) trigger_error("Invalid database configuration (port).", E_USER_ERROR);
		$port = (int) $port[0];
		
		$user = $this->config->xpath('user');
		if (!$user) trigger_error("Invalid database configuration (user).", E_USER_ERROR);
		$user = (string) $user[0];
		
		$pass = $this->config->xpath('pass');
		if (!$pass) trigger_error("Invalid database configuration (pass).", E_USER_ERROR);
		$pass = (string) $pass[0];
		
		// Open database connection
		$this->mysqli = new \mysqli($host, $user, $pass, null, $port);
		if ($this->mysqli->connect_error) {
			// Connection error
			throw new \Exception(
					"Error on connecting to mysql server. " . $this->mysqli->connect_error . " (" . $this->mysqli->connect_errno . ")");
		}
		
		// Set charset
		$this->mysqli->set_charset("utf8");
		
		// Create/Connect database
		$this->initDatabase();
		
		// Init tables
		$this->initTables();
		
		// Register for callbacks
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_IC_5_SECOND, $this, 'handle5Second');
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_IC_BEGINMAP, $this, 'handleBeginMap');
	}

	/**
	 * Destruct database connection
	 */
	public function __destruct() {
		$this->mysqli->close();
	}

	/**
	 * Connect to the defined database (create it if needed)
	 */
	private function initDatabase() {
		$dbname = $this->config->xpath('database');
		if (!$dbname) trigger_error("Invalid database configuration (database).", E_USER_ERROR);
		$dbname = (string) $dbname[0];
		
		// Try to connect
		$result = $this->mysqli->select_db($dbname);
		if (!$result) {
			// Create database
			$query = "CREATE DATABASE `" . $this->escape($dbname) . "`;";
			$result = $this->mysqli->query($query);
			if (!$result) {
				trigger_error(
						"Couldn't create database '" . $dbname . "'. " . $this->mysqli->error . ' (' . $this->mysqli->errno . ')', 
						E_USER_ERROR);
			}
			else {
				// Connect to database
				$result = $this->mysqli->select_db($dbname);
				if (!$result) {
					trigger_error(
							"Couldn't select database '" . $dbname . "'. " . $this->mysqli->error . ' (' . $this->mysqli->errno . ')', 
							E_USER_ERROR);
				}
			}
		}
	}

	/**
	 * Create the needed tables
	 */
	private function initTables() {
		$query = "";
		
		// Players table
		$query .= "CREATE TABLE IF NOT EXISTS `" . self::TABLE_PLAYERS . "` (
			`index` int(11) NOT NULL AUTO_INCREMENT,
			`Login` varchar(100) NOT NULL,
			`NickName` varchar(250) NOT NULL,
			`PlayerId` int(11) NOT NULL,
			`LadderRanking` int(11) NOT NULL,
			`Flags` varchar(50) NOT NULL,
			`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`index`),
			UNIQUE KEY `Login` (`Login`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Store player metadata' AUTO_INCREMENT=1;";
		
		// Maps table
		$query .= "CREATE TABLE IF NOT EXISTS `ic_maps` (
			`index` int(11) NOT NULL AUTO_INCREMENT,
			`UId` varchar(100) NOT NULL,
			`Name` varchar(100) NOT NULL,
			`FileName` varchar(200) NOT NULL,
			`Author` varchar(150) NOT NULL,
			`Environnement` varchar(50) NOT NULL,
			`Mood` varchar(50) NOT NULL,
			`BronzeTime` int(11) NOT NULL DEFAULT '-1',
			`SilverTime` int(11) NOT NULL DEFAULT '-1',
			`GoldTime` int(11) NOT NULL DEFAULT '-1',
			`AuthorTime` int(11) NOT NULL DEFAULT '-1',
			`CopperPrice` int(11) NOT NULL DEFAULT '-1',
			`LapRace` tinyint(1) NOT NULL,
			`NbLaps` int(11) NOT NULL DEFAULT '-1',
			`NbCheckpoints` int(11) NOT NULL DEFAULT '-1',
			`MapType` varchar(100) NOT NULL,
			`MapStyle` varchar(100) NOT NULL,
			`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`index`),
			UNIQUE KEY `UId` (`UId`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Store map metadata' AUTO_INCREMENT=1;";
		
		// Perform queries
		if (!$this->multiQuery($query)) {
			trigger_error("Creating basic tables failed. " . $this->mysqli->error . ' (' . $this->mysqli->errno . ')', E_USER_ERROR);
		}
		
		// Optimize all existing tables
		$query = "SHOW TABLES;";
		$result = $this->query($query);
		if (!$result || !is_object($result)) {
			trigger_error("Couldn't select tables. " . $this->mysqli->error . ' (' . $this->mysqli->errno . ')');
		}
		else {
			$query = "OPTIMIZE TABLE ";
			$count = $result->num_rows;
			$index = 0;
			while ($row = $result->fetch_row()) {
				$query .= "`" . $row[0] . "`";
				if ($index < $count - 1) $query .= ", ";
				$index++;
			}
			$query .= ";";
			if (!$this->query($query)) {
				trigger_error("Couldn't optimize tables. " . $this->mysqli->error . ' (' . $this->mysqli->errno . ')');
			}
		}
	}

	/**
	 * Wrapper for performing a simple query
	 *
	 * @param string $query        	
	 * @return mixed query result
	 */
	public function query($query) {
		if (!is_string($query)) return false;
		if (strlen($query) <= 0) return true;
		return $this->mysqli->query($query);
	}

	/**
	 * Perform multi query
	 *
	 * @param
	 *        	string multi_query
	 * @return bool whether no error occured during executing the multi query
	 */
	public function multiQuery($query) {
		if (!is_string($query)) return false;
		if (strlen($query) <= 0) return true;
		$noError = true;
		$this->mysqli->multi_query($query);
		if ($this->mysqli->error) {
			trigger_error("Executing multi query failed. " . $this->mysqli->error . ' (' . $this->mysqli->errno . ')');
			$noError = false;
		}
		while ($this->mysqli->more_results() && $this->mysqli->next_result()) {
			if ($this->mysqli->error) {
				trigger_error("Executing multi query failed. " . $this->mysqli->error . ' (' . $this->mysqli->errno . ')');
				$noError = false;
			}
		}
		return $noError;
	}

	/**
	 * Handle 5Second callback
	 */
	public function handle5Second($callback = null) {
		// Save current players in database
		$players = $this->iControl->server->getPlayers();
		if ($players) {
			$query = "";
			foreach ($players as $player) {
				if (!Tools::isPlayer($player)) continue;
				$query .= $this->composeInsertPlayer($player);
			}
			$this->multiQuery($query);
		}
	}

	/**
	 * Handle BeginMap callback
	 */
	public function handleBeginMap($callback) {
		$map = $callback[1][0];
		$query = $this->composeInsertMap($map);
		$result = $this->query($query);
		if ($this->mysqli->error) {
			trigger_error("Couldn't save map. " . $this->mysqli->error . ' (' . $this->mysqli->errno . ')');
		}
	}

	/**
	 * Get the player index for the given login
	 *
	 * @param string $login        	
	 * @return int null
	 */
	public function getPlayerIndex($login) {
		$query = "SELECT `index` FROM `" . self::TABLE_PLAYERS . "` WHERE `Login` = '" . $this->escape($login) . "';";
		$result = $this->query($query);
		$result = $result->fetch_assoc();
		if ($result) {
			return $result['index'];
		}
		return null;
	}

	/**
	 * Get the map index for the given UId
	 *
	 * @param string $uid        	
	 * @return int null
	 */
	public function getMapIndex($uid) {
		$query = "SELECT `index` FROM `" . self::TABLE_MAPS . "` WHERE `UId` = '" . $this->escape($uid) . "';";
		$result = $this->query($query);
		$result = $result->fetch_assoc();
		if ($result) {
			return $result['index'];
		}
		return null;
	}

	/**
	 * Compose a query string for inserting the given player
	 *
	 * @param array $player        	
	 */
	private function composeInsertPlayer($player) {
		if (!Tools::isPlayer($player)) return "";
		return "INSERT INTO `" . self::TABLE_PLAYERS . "` (
			`Login`,
			`NickName`,
			`PlayerId`,
			`LadderRanking`,
			`Flags`
			) VALUES (
			'" . $this->escape($player['Login']) . "',
			'" . $this->escape($player['NickName']) . "',
			" . $player['PlayerId'] . ",
			" . $player['LadderRanking'] . ",
			'" . $this->escape($player['Flags']) . "'
			) ON DUPLICATE KEY UPDATE
			`NickName` = VALUES(`NickName`),
			`PlayerId` = VALUES(`PlayerId`),
			`LadderRanking` = VALUES(`LadderRanking`),
			`Flags` = VALUES(`Flags`);";
	}

	/**
	 * Compose a query string for inserting the given map
	 *
	 * @param array $map        	
	 */
	private function composeInsertMap($map) {
		if (!$map) return "";
		return "INSERT INTO `" . self::TABLE_MAPS . "` (
			`UId`,
			`Name`,
			`FileName`,
			`Author`,
			`Environnement`,
			`Mood`,
			`BronzeTime`,
			`SilverTime`,
			`GoldTime`,
			`AuthorTime`,
			`CopperPrice`,
			`LapRace`,
			`NbLaps`,
			`NbCheckpoints`,
			`MapType`,
			`MapStyle`
			) VALUES (
			'" . $this->escape($map['UId']) . "',
			'" . $this->escape($map['Name']) . "',
			'" . $this->escape($map['FileName']) . "',
			'" . $this->escape($map['Author']) . "',
			'" . $this->escape($map['Environnement']) . "',
			'" . $this->escape($map['Mood']) . "',
			" . $map['BronzeTime'] . ",
			" . $map['SilverTime'] . ",
			" . $map['GoldTime'] . ",
			" . $map['AuthorTime'] . ",
			" . $map['CopperPrice'] . ",
			" . Tools::boolToInt($map['LapRace']) . ",
			" . $map['NbLaps'] . ",
			" . $map['NbCheckpoints'] . ",
			'" . $this->escape($map['MapType']) . "',
			'" . $this->escape($map['MapStyle']) . "'
			) ON DUPLICATE KEY UPDATE
			`Name` = VALUES(`Name`),
			`FileName` = VALUES(`FileName`),
			`Author` = VALUES(`Author`),
			`Environnement` = VALUES(`Environnement`),
			`Mood` = VALUES(`Mood`),
			`BronzeTime` = VALUES(`BronzeTime`),
			`SilverTime` = VALUES(`SilverTime`),
			`GoldTime` = VALUES(`GoldTime`),
			`AuthorTime` = VALUES(`AuthorTime`),
			`CopperPrice` = VALUES(`CopperPrice`),
			`LapRace` = VALUES(`LapRace`),
			`NbLaps` = VALUES(`NbLaps`),
			`NbCheckpoints` = VALUES(`NbCheckpoints`),
			`MapType` = VALUES(`MapType`),
			`MapStyle` = VALUES(`MapStyle`);";
	}

	/**
	 * Retrieve all information about the player with the given login
	 */
	public function getPlayer($login) {
		if (!$login) return null;
		$query = "SELECT * FROM `" . self::TABLE_PLAYERS . "` WHERE `Login` = '" . $this->escape($login) . "';";
		$result = $this->mysqli->query($query);
		if ($this->mysqli->error || !$result) {
			trigger_error(
					"Couldn't select player with login '" . $login . "'. " . $this->mysqli->error . ' (' . $this->mysqli->errno . ')');
			return null;
		}
		else {
			while ($player = $result->fetch_assoc()) {
				return $player;
			}
			return null;
		}
	}

	/**
	 * Escapes the given string for a mysql query
	 *
	 * @param string $string        	
	 * @return string
	 */
	public function escape($string) {
		return $this->mysqli->escape_string($string);
	}
}

?>

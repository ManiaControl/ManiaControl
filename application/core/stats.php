<?php

namespace ManiaControl;

/**
 * Stats class
 *
 * @author steeffeen
 */
class Stats {
	/**
	 * Constants
	 */
	const TABLE_STATS_SERVER = 'ic_stats_server';
	const TABLE_STATS_PLAYERS = 'ic_stats_players';

	/**
	 * Private properties
	 */
	private $mc = null;

	private $config = null;

	/**
	 * Constuct stats manager
	 */
	public function __construct($mc) {
		$this->mc = $mc;
		
		// Load config
		$this->config = Tools::loadConfig('stats.ManiaControl.xml');
		$this->loadSettings();
		
		// Init database tables
		$this->initTables();
		
		// Register for needed callbacks
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_ENDMAP, $this, 'handleEndMap');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCHAT, $this, 'handlePlayerChat');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');
		$this->mc->callbacks->registerCallbackHandler(Callbacks::CB_TM_PLAYERFINISH, $this, 'handlePlayerFinish');
	}

	/**
	 * Create the database tables
	 */
	private function initTables() {
		$query = "";
		
		// Server stats
		$query .= "CREATE TABLE IF NOT EXISTS `" . self::TABLE_STATS_SERVER . "` (
		`index` int(11) NOT NULL AUTO_INCREMENT,
		`day` date NOT NULL,
		`connectCount` int(11) NOT NULL DEFAULT '0',
		`maxPlayerCount` int(11) NOT NULL DEFAULT '0',
		`playedMaps` int(11) NOT NULL DEFAULT '0',
		`finishCount` int(11) NOT NULL DEFAULT '0',
		`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`index`),
		UNIQUE KEY `day` (`day`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Stores server stats' AUTO_INCREMENT=1;";
		
		// Player stats
		$query .= "CREATE TABLE IF NOT EXISTS `" . self::TABLE_STATS_PLAYERS . "` (
		`index` int(11) NOT NULL AUTO_INCREMENT,
		`Login` varchar(100) NOT NULL,
		`playTime` int(11) NOT NULL DEFAULT '0',
		`connectCount` int(11) NOT NULL DEFAULT '0',
		`chatCount` int(11) NOT NULL DEFAULT '0',
		`finishCount` int(11) NOT NULL DEFAULT '0',
		`hitCount` int(11) NOT NULL DEFAULT '0',
		`eliminationCount` int(11) NOT NULL DEFAULT '0',
		`lastJoin` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`index`),
		UNIQUE KEY `Login` (`Login`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Tracks player stats' AUTO_INCREMENT=1;";
		
		// Perform queries
		if (!$this->mc->database->multiQuery($query)) {
			trigger_error("Creating stats tables failed.");
		}
	}

	/**
	 * Load settings from config
	 */
	private function loadSettings() {
		$this->settings = new \stdClass();
		
		$this->settings->track_server_connects = Tools::checkSetting($this->config, 'track_server_connects');
		$this->settings->track_server_max_players = Tools::checkSetting($this->config, 'track_server_max_players');
		$this->settings->track_server_played_maps = Tools::checkSetting($this->config, 'track_server_played_maps');
		$this->settings->track_server_finishes = Tools::checkSetting($this->config, 'track_server_finishes');
		
		$this->settings->track_player_connects = Tools::checkSetting($this->config, 'track_player_connects');
		$this->settings->track_player_playtime = Tools::checkSetting($this->config, 'track_player_playtime');
		$this->settings->track_player_chats = Tools::checkSetting($this->config, 'track_player_chats');
		$this->settings->track_player_finishes = Tools::checkSetting($this->config, 'track_player_finishes');
	}

	/**
	 * Handle EndMap callback
	 */
	public function handleEndMap($callback) {
		$multiquery = "";
		
		// Track played server maps
		if ($this->settings->track_server_played_maps) {
			$multiquery .= "INSERT INTO `" . self::TABLE_STATS_SERVER . "` (
			`day`,
			`playedMaps`
			) VALUES (
			CURDATE(),
			1
			) ON DUPLICATE KEY UPDATE
			`playedMaps` = `playedMaps` + VALUES(`playedMaps`)
			;";
		}
		
		// Perform query
		if (!$this->mc->database->multiQuery($multiquery)) {
			trigger_error("Perform queries on end map failed.");
		}
	}

	/**
	 * Handle PlayerChat callback
	 */
	public function handlePlayerChat($callback) {
		if ($callback[1][0] <= 0) return;
		$multiquery = "";
		$login = $callback[1][1];
		
		// Track chats
		if ($this->settings->track_player_chats) {
			$multiquery .= "INSERT INTO `" . self::TABLE_STATS_PLAYERS . "` (
			`Login`,
			`chatCount`
			) VALUES (
			'" . $this->mc->database->escape($login) . "',
			1
			) ON DUPLICATE KEY UPDATE
			`chatCount` = `chatCount` + VALUES(`chatCount`)
			;";
		}
		
		// Perform query
		if (!$this->mc->database->multiQuery($multiquery)) {
			trigger_error("Perform queries on player chat failed.");
		}
	}

	/**
	 * Handle PlayerConnect callback
	 */
	public function handlePlayerConnect($callback) {
		$multiquery = "";
		$login = $callback[1][0];
		
		// Track server connect
		if ($this->settings->track_server_connects) {
			$multiquery .= "INSERT INTO `" . self::TABLE_STATS_SERVER . "` (
			`day`,
			`connectCount`
			) VALUES (
			CURDATE(),
			1
			) ON DUPLICATE KEY UPDATE
			`connectCount` = `connectCount` + VALUES(`connectCount`)
			;";
		}
		
		// Track server max players
		if ($this->settings->track_server_max_players) {
			$players = $this->mc->server->getPlayers();
			$multiquery .= "INSERT INTO `" . self::TABLE_STATS_SERVER . "` (
			`day`,
			`maxPlayerCount`
			) VALUES (
			CURDATE(),
			" . count($players) . "
			) ON DUPLICATE KEY UPDATE
			`maxPlayerCount` = GREATEST(`maxPlayerCount`, VALUES(`maxPlayerCount`))
			;";
		}
		
		// Track player connect
		if ($this->settings->track_player_connects) {
			$multiquery .= "INSERT INTO `" . self::TABLE_STATS_PLAYERS . "` (
			`Login`,
			`lastJoin`,
			`connectCount`
			) VALUES (
			'" . $this->mc->database->escape($login) . "',
			NOW(),
			1
			) ON DUPLICATE KEY UPDATE
			`lastJoin` = VALUES(`lastJoin`),
			`connectCount` = `connectCount` + VALUES(`connectCount`)
			;";
		}
		
		// Perform query
		if (!$this->mc->database->multiQuery($multiquery)) {
			trigger_error("Perform queries on player connect failed.");
		}
	}

	/**
	 * Handle PlayerDisconnect callback
	 */
	public function handlePlayerDisconnect($callback) {
		$multiquery = "";
		$login = $callback[1][0];
		
		// Track player playtime
		if ($this->settings->track_player_playtime) {
			$query = "SELECT `lastJoin` FROM `" . self::TABLE_STATS_PLAYERS . "`
			WHERE `Login` = '" . $this->mc->database->escape($login) . "'
			;";
			$result = $this->mc->database->query($query);
			if (!$result) {
				// Error
				trigger_error("Error selecting player join time from '" . $login . "'.");
			}
			else {
				// Add play time
				while ($row = $result->fetch_object()) {
					if (!property_exists($row, 'lastJoin')) continue;
					$lastJoin = strtotime($row->lastJoin);
					$lastJoin = ($lastJoin > $this->mc->startTime ? $lastJoin : $this->mc->startTime);
					$multiquery .= "INSERT INTO `" . self::TABLE_STATS_PLAYERS . "` (
					`Login`,
					`playTime`
					) VALUES (
					'" . $this->mc->database->escape($login) . "',
					TIMESTAMPDIFF(SECOND, '" . Tools::timeToTimestamp($lastJoin) . "', NOW())
					) ON DUPLICATE KEY UPDATE
					`playTime` = `playTime` + VALUES(`playTime`)
					;";
					break;
				}
			}
		}
		
		// Perform query
		if (!$this->mc->database->multiQuery($multiquery)) {
			trigger_error("Perform queries on player connect failed.");
		}
	}

	/**
	 * Handle the PlayerFinish callback
	 */
	public function handlePlayerFinish($callback) {
		if ($callback[1][0] <= 0) return;
		if ($callback[1][2] <= 0) return;
		
		$multiquery = "";
		$login = $callback[1][1];
		
		// Track server finishes
		if ($this->settings->track_server_finishes) {
			$multiquery .= "INSERT INTO `" . self::TABLE_STATS_SERVER . "` (
			`day`,
			`finishCount`
			) VALUES (
			CURDATE(),
			1
			) ON DUPLICATE KEY UPDATE
			`finishCount` = `finishCount` + VALUES(`finishCount`)
			;";
		}
		
		// Track player finishes
		if ($this->settings->track_player_finishes) {
			$multiquery .= "INSERT INTO `" . self::TABLE_STATS_PLAYERS . "` (
			`Login`,
			`finishCount`
			) VALUES (
			'" . $this->mc->database->escape($login) . "',
			1
			) ON DUPLICATE KEY UPDATE
			`finishCount` = `finishCount` + VALUES(`finishCount`)
			;";
		}
		
		// Perform query
		if (!$this->mc->database->multiQuery($multiquery)) {
			trigger_error("Perform queries on player finish failed.");
		}
	}
}

?>

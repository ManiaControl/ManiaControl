<?php

namespace ManiaControl;

require_once __DIR__ . '/player.php';

/**
 * Class managing players
 *
 * @author kremsy & steeffeen
 */
class PlayerHandler {
	/**
	 * Constants
	 */
	const TABLE_PLAYERS = 'mc_players';
	
	/**
	 * Public properties
	 */
	public $rightLevels = array(0 => 'Player', 1 => 'Operator', 2 => 'Admin', 3 => 'MasterAdmin', 4 => 'Owner');
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $playerList = array();

	/**
	 * Construct player handler
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		$this->initTables();
		
		$this->maniaControl->callbacks->registerCallbackHandler(Callbacks::CB_MC_ONINIT, $this, 'onInit');
		$this->maniaControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCONNECT, $this, 'playerConnect');
		$this->maniaControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERDISCONNECT, $this, 'playerDisconnect');
	}

	/**
	 * Initialize all necessary tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$playerTableQuery = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_PLAYERS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`nickname` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
				`path` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`totalPlayed` int(11) NOT NULL DEFAULT '0' COMMENT 'Seconds',
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `login` (`login`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Player data' AUTO_INCREMENT=1;";
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
	 * Handle OnInit callback
	 *
	 * @param array $callback        	
	 */
	public function onInit(array $callback) {
		$this->maniaControl->client->query('GetPlayerList', 300, 0, 2);
		$playerList = $this->maniaControl->client->getResponse();
		foreach ($playerList as $player) {
			if ($player['PlayerId'] <= 0) {
				continue;
			}
			$callback = array(Callbacks::CB_MP_PLAYERCONNECT, array($player['Login']));
			$this->playerConnect($callback);
		}
	}

	/**
	 * Handle playerConnect callback
	 *
	 * @param array $callback        	
	 */
	public function playerConnect(array $callback) {
		$login = $callback[1][0];
		$this->maniaControl->client->query('GetDetailedPlayerInfo', $login);
		$playerInfo = $this->maniaControl->client->getResponse();
		$player = new Player($playerInfo);
		$this->addPlayer($player);
	}

	/**
	 * Handle playerDisconnect callback
	 *
	 * @param array $callback        	
	 */
	public function playerDisconnect(array $callback) {
		$login = $callback[1][0];
		$player = $this->removePlayer($login);
	}

	/**
	 * Get a Player from the PlayerList
	 *
	 * @param string $login        	
	 * @return Player
	 */
	public function getPlayer($login) {
		if (!isset($this->playerList[$login])) {
			return null;
		}
		return $this->playerList[$login];
	}

	/**
	 * Add a player to the PlayerList
	 *
	 * @param Player $player        	
	 * @return bool
	 */
	private function addPlayer(Player $player) {
		if (!$player) {
			return false;
		}
		$this->savePlayer($player);
		$this->playerList[$player->login] = $player;
		return true;
	}

	/**
	 * Remove a Player from the PlayerList
	 *
	 * @param string $login        	
	 * @param bool $savePlayedTime        	
	 * @return Player $player
	 */
	private function removePlayer($login, $savePlayedTime = true) {
		if (!isset($this->playerList[$login])) {
			return null;
		}
		$player = $this->playerList[$login];
		unset($this->playerList[$login]);
		if ($savePlayedTime) {
			$this->updatePlayedTime($player);
		}
		return $player;
	}

	/**
	 * Save player in database and fill up object properties
	 *
	 * @param Player $player        	
	 * @param int $joinCount        	
	 * @return bool
	 */
	private function savePlayer(Player &$player, $joinCount = 1) {
		if (!$player) {
			return false;
		}
		$mysqli = $this->maniaControl->database->mysqli;
		
		// Save player
		$playerQuery = "INSERT INTO `" . self::TABLE_PLAYERS . "` (
				`login`,
				`nickname`,
				`path`,
				`joinCount`
				) VALUES (
				?, ?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`index` = LAST_INSERT_ID(`index`),
				`nickname` = VALUES(`nickname`),
				`path` = VALUES(`path`),
				`joinCount` = `joinCount` + VALUES(`joinCount`);";
		$playerStatement = $mysqli->prepare($playerQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$playerStatement->bind_param('sssi', $player->login, $player->nickname, $player->path, $joinCount);
		$playerStatement->execute();
		if ($playerStatement->error) {
			trigger_error($playerStatement->error);
			$playerStatement->close();
			return false;
		}
		$player->index = $playerStatement->insert_id;
		$playerStatement->close();
		
		// Fill up properties
		$playerQuery = "SELECT `joinCount`, `totalPlayed` FROM `" . self::TABLE_PLAYERS . "`
				WHERE `index` = ?;";
		$playerStatement = $mysqli->prepare($playerQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$playerStatement->bind_param('i', $player->index);
		$playerStatement->execute();
		if ($playerStatement->error) {
			trigger_error($playerStatement->error);
			$playerStatement->close();
			return false;
		}
		$playerStatement->store_result();
		$playerStatement->bind_result($player->joinCount, $player->totalPlayed);
		$playerStatement->fetch();
		$playerStatement->free_result();
		$playerStatement->close();
		
		return true;
	}

	/**
	 * Update total played time of the player
	 *
	 * @param Player $player        	
	 * @return bool
	 */
	private function updatePlayedTime(Player $player) {
		if (!$player) {
			return false;
		}
		$playedTime = time() - $player->joinTime;
		$mysqli = $this->maniaControl->database->mysqli;
		$playedQuery = "UPDATE `" . self::TABLE_PLAYERS . "`
				SET `totalPlayed` = `totalPlayed` + ?;";
		$playedStatement = $mysqli->prepare($playedQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$playedStatement->bind_param('i', $playedTime);
		$playedStatement->execute();
		if ($playedStatement->error) {
			trigger_error($playedStatement->error);
			$playedStatement->close();
			return false;
		}
		$playedStatement->close();
		return true;
	}
} 
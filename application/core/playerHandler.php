<?php

namespace ManiaControl;

require_once __DIR__ . '/player.php';

/**
 * Class managing players
 *
 * @package ManiaControl
 */
class playerHandler {
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
				`pid` int(11) NOT NULL DEFAULT '-1',
				`login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`ipFull` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`clientVersion` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`zone` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
				`language` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`avatar` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
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
		$this->playerList[$player->login] = $player;
		return true;
	}

	/**
	 * Remove a Player from the PlayerList
	 *
	 * @param string $login        	
	 * @return Player $player
	 */
	private function removePlayer($login) {
		if (!isset($this->playerList[$login])) {
			return null;
		}
		$player = $this->playerList[$login];
		unset($this->playerList[$login]);
		return $player;
	}
} 
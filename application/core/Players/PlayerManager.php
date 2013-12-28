<?php

namespace ManiaControl\Players;

require_once __DIR__ . '/Player.php';
require_once __DIR__ . '/PlayerCommands.php';

use FML\Controls\Quad;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;

/**
 * Class managing players
 *
 * @author kremsy & steeffeen
 */
class PlayerManager implements CallbackListener {
	/**
	 * Constants
	 */
	const CB_PLAYERJOINED = 'PlayerManagerCallback.PlayerJoined';
	const CB_ONINIT = 'PlayerManagerCallback.OnInit';
	const CB_PLAYERINFOCHANGED = 'PlayerManagerCallback.PlayerInfoChanged';
	const TABLE_PLAYERS = 'mc_players';
	const SETTING_JOIN_LEAVE_MESSAGES = 'Enable Join & Leave Messages';

	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $playerCommands = null;
	private $playerList = array();

	/**
	 * Public properties
	 */
	public $playerActions = null;

	/**
	 * Construct player manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();
		
		$this->playerCommands = new PlayerCommands($maniaControl);
		$this->playerActions = new PlayerActions($maniaControl);

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES, true);
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'onInit');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERCONNECT, $this, 'playerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERDISCONNECT, $this, 'playerDisconnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERINFOCHANGED, $this, 'playerInfoChanged');

	}

	/**
	 * Initialize necessary database tables
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
				`authLevel` int(11) NOT NULL DEFAULT '0',
				`joinCount` int(11) NOT NULL DEFAULT '0',
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
		// Add all players
		$this->maniaControl->client->query('GetPlayerList', 300, 0, 2);
		$playerList = $this->maniaControl->client->getResponse();
		foreach ($playerList as $playerItem) {
			if ($playerItem['PlayerId'] <= 0) {
				continue;
			}
			$this->maniaControl->client->query('GetDetailedPlayerInfo', $playerItem['Login']);
			$playerInfo = $this->maniaControl->client->getResponse();
			$player = new Player($playerInfo);
			$this->addPlayer($player);
		}

		// Trigger own callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_ONINIT, array(self::CB_ONINIT));
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
		
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES)) {
			$string = array(0 => '$0f0Player', 1 => '$0f0Moderator', 2 => '$0f0Admin', 3 => '$0f0MasterAdmin', 4 => '$0f0MasterAdmin');
			//$nickname = Formatter::stripCodes($player->nickname); // TODO: strip codes without colour codes like in serverviewer

			//TODO standart notification colour from settings or something

			$this->maniaControl->chat->sendChat(
					'$s$0f0' . $string[$player->authLevel] . ' $fff' . $player->nickname . '$z$s$0f0 Nation:$fff ' . $player->getCountry() . ' $z$s$0f0joined');
			$this->maniaControl->chat->sendInformation('This server uses ManiaControl v' . ManiaControl::VERSION,$player->login);
		}

		$this->maniaControl->log('Player joined: ' . $player->login . " / " . $player->nickname . " Nation:" . $player->getCountry() . " IP: " .$player->ipAddress);

		// Trigger own callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_PLAYERJOINED, array(self::CB_PLAYERJOINED, $player));
	}

	/**
	 * Handle playerDisconnect callback
	 *
	 * @param array $callback        	
	 */
	public function playerDisconnect(array $callback) {
		$login = $callback[1][0];
		$player = $this->removePlayer($login);

		//if($player->isFakePlayer())
			//return;

		$played = Formatter::formatTimeH(time() - $player->joinTime);
		$this->maniaControl->log("Player left: " . $player->login . " / " . $player->nickname . " Playtime: " . $played);



		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES)) {
			$this->maniaControl->chat->sendChat('$<' . $player->nickname . '$> $s$0f0has left the game');
		}
	}

	/**
	 * Update PlayerInfo
	 * @param array $callback
	 */
	public function playerInfoChanged(array $callback){
		$player = $this->getPlayer($callback[1][0]['Login']);
		if($player == null)
			return;

		$player->teamId = $callback[1][0]["TeamId"];
		$player->isSpectator = $callback[1][0]["SpectatorStatus"];
		$player->ladderRank = $callback[1][0]["LadderRanking"];

		// Trigger own callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_PLAYERINFOCHANGED, array(self::CB_PLAYERINFOCHANGED));
	}

	/**
	 * Get the complete PlayerList
	 *
	 * @return array
	 */
	public function getPlayers() {
		return $this->playerList;
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
		$playerQuery = "SELECT `authLevel`, `joinCount`, `totalPlayed` FROM `" . self::TABLE_PLAYERS . "`
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
		$playerStatement->bind_result($player->authLevel, $player->joinCount, $player->totalPlayed);
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

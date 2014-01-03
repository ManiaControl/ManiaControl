<?php

namespace ManiaControl\Players;

require_once __DIR__ . '/Player.php';
require_once __DIR__ . '/PlayerCommands.php';
require_once __DIR__ . '/PlayerDetailed.php';

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Statistics\StatisticManager;

/**
 * Class managing players
 *
 * @author kremsy & steeffeen
 */
class PlayerManager implements CallbackListener {
	/**
	 * Constants
	 */
	const CB_PLAYERJOINED             = 'PlayerManagerCallback.PlayerJoined';
	const CB_PLAYERDISCONNECTED       = 'PlayerManagerCallback.PlayerDisconnected';
	const CB_ONINIT                   = 'PlayerManagerCallback.OnInit';
	const CB_PLAYERINFOCHANGED        = 'PlayerManagerCallback.PlayerInfoChanged';
	const TABLE_PLAYERS               = 'mc_players';
	const SETTING_JOIN_LEAVE_MESSAGES = 'Enable Join & Leave Messages';
	const STAT_JOIN_COUNT             = 'Joins';
	const STAT_PLAYTIME               = 'Playtime';

	/**
	 * Public Properties
	 */
	public $playerActions = null;
	public $playerCommands = null;
	public $playerDetailed = null;
	public $playerList = array();

	/**
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Construct a new Player Manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		$this->playerCommands = new PlayerCommands($maniaControl);
		$this->playerActions  = new PlayerActions($maniaControl);
		$this->playerDetailed  = new PlayerDetailed($maniaControl);

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES, true);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'onInit');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERCONNECT, $this, 'playerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERDISCONNECT, $this, 'playerDisconnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERINFOCHANGED, $this, 'playerInfoChanged');

		// Define player stats
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_JOIN_COUNT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_PLAYTIME, StatisticManager::STAT_TYPE_TIME);
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli               = $this->maniaControl->database->mysqli;
		$playerTableQuery     = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_PLAYERS . "` (
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
		if($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$playerTableStatement->execute();
		if($playerTableStatement->error) {
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
		foreach($playerList as $playerItem) {
			if($playerItem['PlayerId'] <= 0) {
				continue;
			}
			$this->maniaControl->client->query('GetDetailedPlayerInfo', $playerItem['Login']);
			$playerInfo = $this->maniaControl->client->getResponse();
			$player     = new Player($playerInfo);
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
		$player     = new Player($playerInfo);

		$this->addPlayer($player);

		if($this->maniaControl->settingManager->getSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES) && !$player->isFakePlayer()) {
			$string      = array(0 => '$0f0Player', 1 => '$0f0Moderator', 2 => '$0f0Admin', 3 => '$0f0MasterAdmin', 4 => '$0f0MasterAdmin');
			$chatMessage = '$s$0f0' . $string[$player->authLevel] . ' $fff' . $player->nickname . '$z$s$0f0 Nation:$fff ' . $player->getCountry() . ' $z$s$0f0joined!';
			$this->maniaControl->chat->sendChat($chatMessage);
			$this->maniaControl->chat->sendInformation('This server uses ManiaControl v' . ManiaControl::VERSION . '!', $player->login);
		}

		$logMessage = "Player joined: {$player->login} / " . Formatter::stripCodes($player->nickname) . " Nation: " . $player->getCountry() . " IP: {$player->ipAddress}";
		$this->maniaControl->log($logMessage);

		// Trigger own PlayerJoined callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_PLAYERJOINED, array(self::CB_PLAYERJOINED, $player));
	}

	/**
	 * Handle playerDisconnect callback
	 *
	 * @param array $callback
	 */
	public function playerDisconnect(array $callback) {
		$login  = $callback[1][0];
		$player = $this->removePlayer($login);

		// Trigger own callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_PLAYERDISCONNECTED, array(self::CB_PLAYERDISCONNECTED, $player));

		if($player == null || $player->isFakePlayer()) {
			return;
		}

		$played     = Formatter::formatTimeH(time() - $player->joinTime);
		$logMessage = "Player left: {$player->login} / {$player->nickname} Playtime: {$played}";
		$this->maniaControl->log(Formatter::stripCodes($logMessage));

		if($this->maniaControl->settingManager->getSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES)) {
			$this->maniaControl->chat->sendChat('$<' . $player->nickname . '$> $s$0f0has left the game');
		}
	}

	/**
	 * Update PlayerInfo
	 *
	 * @param array $callback
	 */
	public function playerInfoChanged(array $callback) {
		$player = $this->getPlayer($callback[1][0]['Login']);
		if($player == null) {
			return;
		}

		$player->teamId      = $callback[1][0]["TeamId"];
		$player->isSpectator = $callback[1][0]["SpectatorStatus"];
		$player->ladderRank  = $callback[1][0]["LadderRanking"];

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
		if(!isset($this->playerList[$login])) {
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
		if(!$player) {
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
	 * @param bool   $savePlayedTime
	 * @return Player $player
	 */
	private function removePlayer($login, $savePlayedTime = true) {
		if(!isset($this->playerList[$login])) {
			return null;
		}
		$player = $this->playerList[$login];
		unset($this->playerList[$login]);
		if($savePlayedTime) {
			$this->updatePlayedTime($player);
		}
		return $player;
	}

	/**
	 * Save player in database and fill up object properties
	 *
	 * @param Player $player
	 * @param int    $joinCount
	 * @return bool
	 */
	private function savePlayer(Player &$player) {
		$mysqli = $this->maniaControl->database->mysqli;

		// Save player
		$playerQuery     = "INSERT INTO `" . self::TABLE_PLAYERS . "` (
				`login`,
				`nickname`,
				`path`
				) VALUES (
				?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`index` = LAST_INSERT_ID(`index`),
				`nickname` = VALUES(`nickname`),
				`path` = VALUES(`path`);";
		$playerStatement = $mysqli->prepare($playerQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$playerStatement->bind_param('sss', $player->login, $player->nickname, $player->path);
		$playerStatement->execute();
		if($playerStatement->error) {
			trigger_error($playerStatement->error);
			$playerStatement->close();
			return false;
		}
		$player->index = $playerStatement->insert_id;
		$playerStatement->close();

		// Get Player Auth Level from DB
		$playerQuery     = "SELECT `authLevel` FROM `" . self::TABLE_PLAYERS . "` WHERE `index` = ?;";
		$playerStatement = $mysqli->prepare($playerQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$playerStatement->bind_param('i', $player->index);
		$playerStatement->execute();
		if($playerStatement->error) {
			trigger_error($playerStatement->error);
			$playerStatement->close();
			return false;
		}
		$playerStatement->store_result();
		$playerStatement->bind_result($player->authLevel);
		$playerStatement->fetch();
		$playerStatement->free_result();
		$playerStatement->close();

		// Increment the Player Join Count
		$success = $this->maniaControl->statisticManager->incrementStat(self::STAT_JOIN_COUNT, $player, $this->maniaControl->server->getLogin());

		if(!$success) {
			trigger_error("Error while setting the JoinCount");
		}

		return true;
	}

	/**
	 * Update total played time of the player
	 *
	 * @param Player $player
	 * @return bool
	 */
	private function updatePlayedTime(Player $player) {
		if(!$player) {
			return false;
		}
		$playedTime = time() - $player->joinTime;

		return $this->maniaControl->statisticManager->insertStat(self::STAT_PLAYTIME, $player, $this->maniaControl->server->getLogin(), $playedTime);
	}
}

<?php

namespace ManiaControl\Players;

use ManiaControl\Admin\AdminLists;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Statistics\StatisticManager;

/**
 * Class managing Players
 *
 * @author kremsy & steeffeen
 */
class PlayerManager implements CallbackListener, TimerListener {
	/**
	 * Constants
	 */
	const CB_PLAYERCONNECT            = 'PlayerManagerCallback.PlayerConnect';
	const CB_PLAYERDISCONNECT         = 'PlayerManagerCallback.PlayerDisconnect';
	const CB_PLAYERINFOCHANGED        = 'PlayerManagerCallback.PlayerInfoChanged';
	const TABLE_PLAYERS               = 'mc_players';
	const SETTING_JOIN_LEAVE_MESSAGES = 'Enable Join & Leave Messages';
	const STAT_JOIN_COUNT             = 'Joins';
	const STAT_SERVERTIME             = 'Servertime';

	/**
	 * Public Properties
	 */
	public $playerActions = null;
	public $playerCommands = null;
	public $playerDetailed = null;
	public $playerList = null;
	public $adminLists = null;
	public $players = array();

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
		$this->playerDetailed = new PlayerDetailed($maniaControl);
		$this->playerList     = new PlayerList($this->maniaControl);
		$this->adminLists     = new AdminLists($this->maniaControl);

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES, true);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_ONINIT, $this, 'onInit');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERCONNECT, $this, 'playerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERDISCONNECT, $this, 'playerDisconnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERINFOCHANGED, $this, 'playerInfoChanged');

		// Define player stats
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_JOIN_COUNT);
		$this->maniaControl->statisticManager->defineStatMetaData(self::STAT_SERVERTIME, StatisticManager::STAT_TYPE_TIME);
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
				`login` varchar(100) NOT NULL,
				`nickname` varchar(150) NOT NULL,
				`path` varchar(100) NOT NULL,
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
	 * Handle OnInit callback
	 */
	public function onInit() {
		// Add all players
		$players = $this->maniaControl->client->getPlayerList(300, 0, 2);
		foreach($players as $playerItem) {
			if ($playerItem->playerId <= 0) {
				continue;
			}

			$detailedPlayerInfo        = $this->maniaControl->client->getDetailedPlayerInfo($playerItem->login);
			$playerItem->path          = $detailedPlayerInfo->path;
			$playerItem->language      = $detailedPlayerInfo->language;
			$playerItem->clientVersion = $detailedPlayerInfo->clientVersion;
			$playerItem->iPAddress     = $detailedPlayerInfo->iPAddress;
			$playerItem->isSpectator   = $detailedPlayerInfo->isSpectator;
			$playerItem->avatar        = $detailedPlayerInfo->avatar;
			$playerItem->ladderStats   = $detailedPlayerInfo->ladderStats;
			$playerItem->downloadRate  = $detailedPlayerInfo->downloadRate;
			$playerItem->uploadRate    = $detailedPlayerInfo->uploadRate;

			$playerItem->hoursSinceZoneInscription = $detailedPlayerInfo->hoursSinceZoneInscription;

			//Check if the Player is in a Team, to notify if its a TeamMode or not
			if ($playerItem->teamId != -1) {
				$this->maniaControl->server->setTeamMode(true);
			}

			$player                = new Player($playerItem);
			$player->hasJoinedGame = true;
			$this->addPlayer($player);
		}
	}

	/**
	 * Handle playerConnect callback
	 *
	 * @param array $callback
	 */
	public function playerConnect(array $callback) {
		$login      = $callback[1][0];
		$playerInfo = $this->maniaControl->client->getDetailedPlayerInfo($login);
		$player     = new Player($playerInfo);

		$this->addPlayer($player);
	}

	/**
	 * Handle playerDisconnect callback
	 *
	 * @param array $callback
	 */
	public function playerDisconnect(array $callback) {
		$login  = $callback[1][0];
		$player = $this->removePlayer($login);

		if ($player == null) {
			return;
		}
		// Trigger own callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_PLAYERDISCONNECT, $player);

		if ($player->isFakePlayer()) {
			return;
		}

		$played     = Formatter::formatTimeH(time() - $player->joinTime);
		$logMessage = "Player left: {$player->login} / {$player->nickname} Playtime: {$played}";
		$this->maniaControl->log(Formatter::stripCodes($logMessage));

		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES)) {
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
		if ($player == null) {
			return;
		}

		$player->ladderRank = $callback[1][0]["LadderRanking"];
		$player->teamId     = $callback[1][0]["TeamId"];

		//Check if the Player is in a Team, to notify if its a TeamMode or not
		if ($player->teamId != -1) {
			$this->maniaControl->server->setTeamMode(true);
		}

		$prevJoinState = $player->hasJoinedGame;

		$player->updatePlayerFlags($callback[1][0]["Flags"]);
		$player->updateSpectatorStatus($callback[1][0]["SpectatorStatus"]);

		//Check if Player finished joining the game
		if ($player->hasJoinedGame && !$prevJoinState) {

			if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES) && !$player->isFakePlayer()) {
				$string      = array(0 => '$0f0Player', 1 => '$0f0Moderator', 2 => '$0f0Admin', 3 => '$0f0SuperAdmin', 4 => '$0f0MasterAdmin');
				$chatMessage = '$s$0f0' . $string[$player->authLevel] . ' $fff' . $player->nickname . '$z$s$0f0 Nation:$fff ' . $player->getCountry() . ' $z$s$0f0joined!';
				$this->maniaControl->chat->sendChat($chatMessage);
				$this->maniaControl->chat->sendInformation('This server uses ManiaControl v' . ManiaControl::VERSION . '!', $player->login);
			}

			$logMessage = "Player joined: {$player->login} / " . Formatter::stripCodes($player->nickname) . " Nation: " . $player->getCountry() . " IP: {$player->ipAddress}";
			$this->maniaControl->log($logMessage);

			// Increment the Player Join Count
			$this->maniaControl->statisticManager->incrementStat(self::STAT_JOIN_COUNT, $player, $this->maniaControl->server->index);

			// Trigger own PlayerJoined callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_PLAYERCONNECT, $player);

		}

		// Trigger own callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_PLAYERINFOCHANGED, $player);
	}


	/**
	 * Get all Players
	 *
	 * @return array
	 */
	public function getPlayers() {
		return $this->players;
	}

	/**
	 * Gets the Count of all Player
	 *
	 * @return int
	 */
	public function getPlayerCount() {
		$count = 0;
		foreach($this->players as $player) {
			/** @var Player $player */
			if (!$player->isSpectator) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Gets the Count of all Spectators
	 *
	 * @return int
	 */
	public function getSpectatorCount() {
		$count = 0;
		foreach($this->players as $player) {
			/** @var Player $player */
			if ($player->isSpectator) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Gets a Player by his index
	 *
	 * @param $index
	 * @return Player|null
	 */
	public function getPlayerByIndex($index) {
		foreach($this->players as $player) {
			/** @var Player $player */
			if ($player->index == $index) {
				return $player;
			}
		}
		//Player is not online -> get Player from Database
		return $this->getPlayerFromDatabaseByIndex($index);
	}

	/**
	 * Get a Player by Login
	 *
	 * @param string $login
	 * @return \ManiaControl\Players\Player
	 */
	public function getPlayer($login) {
		if (!isset($this->players[$login])) {
			return $this->getPlayerFromDatabaseByLogin($login);
		}
		return $this->players[$login];
	}

	/**
	 * Add a player
	 *
	 * @param Player $player
	 * @return bool
	 */
	private function addPlayer(Player $player) {
		$this->savePlayer($player);
		$this->players[$player->login] = $player;
		return true;
	}

	/**
	 * Remove a Player
	 *
	 * @param string $login
	 * @param bool   $savePlayedTime
	 * @return Player $player
	 */
	private function removePlayer($login, $savePlayedTime = true) {
		if (!isset($this->players[$login])) {
			return null;
		}
		$player = $this->players[$login];
		unset($this->players[$login]);
		if ($savePlayedTime) {
			$this->updatePlayedTime($player);
		}
		return $player;
	}


	/**
	 * Get's a Player out of the database
	 *
	 * @param $playerIndex
	 * @return Player $player
	 */
	private function getPlayerFromDatabaseByIndex($playerIndex) {
		$mysqli = $this->maniaControl->database->mysqli;

		if (!is_numeric($playerIndex)) {
			return null;
		}

		$query  = "SELECT * FROM `" . self::TABLE_PLAYERS . "` WHERE `index` = " . $playerIndex . ";";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return null;
		}

		$row = $result->fetch_object();
		$result->close();

		if (!isset($row)) {
			return null;
		}

		$player              = new Player(false);
		$player->index       = $playerIndex;
		$player->login       = $row->login;
		$player->rawNickname = $row->nickname;
		$player->nickname    = Formatter::stripDirtyCodes($player->rawNickname);
		$player->path        = $row->path;
		$player->authLevel   = $row->authLevel;

		return $player;
	}


	/**
	 * Get's a Player out of the database
	 *
	 * @param $playerIndex
	 * @return Player $player
	 */
	private function getPlayerFromDatabaseByLogin($playerLogin) {
		$mysqli = $this->maniaControl->database->mysqli;

		$query  = "SELECT * FROM `" . self::TABLE_PLAYERS . "` WHERE `login` LIKE '" . $mysqli->escape_string($playerLogin) . "';";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return null;
		}

		$row = $result->fetch_object();
		$result->close();

		if (!isset($row)) {
			return null;
		}

		$player              = new Player(false);
		$player->index       = $row->index;
		$player->login       = $row->login;
		$player->rawNickname = $row->nickname;
		$player->nickname    = Formatter::stripDirtyCodes($player->rawNickname);
		$player->path        = $row->path;
		$player->authLevel   = $row->authLevel;

		return $player;
	}

	/**
	 * Save player in Database and fill up Object Properties
	 *
	 * @param Player $player
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
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$playerStatement->bind_param('sss', $player->login, $player->rawNickname, $player->path);
		$playerStatement->execute();
		if ($playerStatement->error) {
			trigger_error($playerStatement->error);
			$playerStatement->close();
			return false;
		}
		$player->index = $playerStatement->insert_id;
		$playerStatement->close();

		// Get Player Auth Level from DB
		$playerQuery     = "SELECT `authLevel` FROM `" . self::TABLE_PLAYERS . "` WHERE `index` = ?;";
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
		$playerStatement->bind_result($player->authLevel);
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

		return $this->maniaControl->statisticManager->insertStat(self::STAT_SERVERTIME, $player, $this->maniaControl->server->index, $playedTime);
	}
}

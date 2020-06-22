<?php

namespace ManiaControl\Players;

use ManiaControl\Admin\AdminLists;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Communication\CommunicationAnswer;
use ManiaControl\Communication\CommunicationListener;
use ManiaControl\Communication\CommunicationMethods;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Statistics\StatisticManager;
use ManiaControl\Utils\Formatter;
use Maniaplanet\DedicatedServer\Xmlrpc\ParseException;
use Maniaplanet\DedicatedServer\Xmlrpc\UnknownPlayerException;

/**
 * Class managing Players
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerManager implements CallbackListener, TimerListener, CommunicationListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const CB_PLAYERCONNECT    = 'PlayerManagerCallback.PlayerConnect';
	const CB_PLAYERDISCONNECT = 'PlayerManagerCallback.PlayerDisconnect';
	/** @use CB_PlayerInfosChanged in favour to avoid multiple triggers at once */
	const CB_PLAYERINFOCHANGED                  = 'PlayerManagerCallback.PlayerInfoChanged';
	const CB_PLAYERINFOSCHANGED                 = 'PlayerManagerCallback.PlayerInfosChanged';
	const CB_SERVER_EMPTY                       = 'PlayerManagerCallback.ServerEmpty';
	const TABLE_PLAYERS                         = 'mc_players';
	const SETTING_JOIN_LEAVE_COLORING           = 'Enable Join & Leave Coloring';
	const SETTING_JOIN_LEAVE_MESSAGES           = 'Enable Join & Leave Messages';
	const SETTING_JOIN_LEAVE_MESSAGES_SPECTATOR = 'Enable Join & Leave Messages for Spectators';
	const STAT_JOIN_COUNT                       = 'Joins';
	const STAT_SERVERTIME                       = 'Servertime';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var Player[] $players */
	private $players = array();

	/** @var PlayerActions $playerActions */
	private $playerActions = null;

	/** @var PlayerCommands $playerCommands */

	private $playerCommands = null;
	/** @var PlayerDetailed $playerDetailed */

	private $playerDetailed = null;

	/** @var PlayerDataManager $playerDataManager */
	private $playerDataManager = null;

	/** @var PlayerList $playerList */
	private $playerList = null;

	/** @var AdminLists $adminLists */
	private $adminLists = null;

	private $playerInfosChangedTime = 0;

	/**
	 * Construct a new Player Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		$this->playerCommands    = new PlayerCommands($maniaControl);
		$this->playerActions     = new PlayerActions($maniaControl);
		$this->playerDetailed    = new PlayerDetailed($maniaControl);
		$this->playerDataManager = new PlayerDataManager($maniaControl);
		$this->playerList        = new PlayerList($maniaControl);
		$this->adminLists        = new AdminLists($maniaControl);

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_JOIN_LEAVE_COLORING, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_JOIN_LEAVE_MESSAGES_SPECTATOR, true);

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'onInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERCONNECT, $this, 'playerConnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERDISCONNECT, $this, 'playerDisconnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERINFOCHANGED, $this, 'playerInfoChanged');

		// Player stats
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_JOIN_COUNT);
		$this->maniaControl->getStatisticManager()->defineStatMetaData(self::STAT_SERVERTIME, StatisticManager::STAT_TYPE_TIME);


		// Communication Listenings
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::GET_PLAYER_LIST, $this, function ($data) {
			return new CommunicationAnswer($this->players);
		});
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli               = $this->maniaControl->getDatabase()->getMysqli();
		$playerTableQuery     = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_PLAYERS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`login` varchar(100) NOT NULL,
				`nickname` varchar(150) NOT NULL DEFAULT '',
				`path` varchar(100) NOT NULL DEFAULT '',
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
			$playerTableStatement->close();
			return false;
		}

		//TODO remove later again (added in v0.165)
		//For Mysql 5.7 add Default Values
		$alterQuery = "ALTER TABLE `" . self::TABLE_PLAYERS . "` CHANGE nickname nickname varchar(150) DEFAULT ''";
		$result     = $mysqli->query($alterQuery);
		if (!$result) {
			trigger_error($mysqli->error);
			return false;
		}

		$alterQuery = "ALTER TABLE `" . self::TABLE_PLAYERS . "` CHANGE path path varchar(100) DEFAULT ''";
		$result     = $mysqli->query($alterQuery);
		if (!$result) {
			trigger_error($mysqli->error);
			return false;
		}

		$alterQuery = "ALTER TABLE `" . self::TABLE_PLAYERS . "` CHANGE authLevel authLevel int(11) DEFAULT 0";
		$result     = $mysqli->query($alterQuery);
		if (!$result) {
			trigger_error($mysqli->error);
			return false;
		}

		$playerTableStatement->close();
		return true;
	}

	/**
	 * Return the player actions
	 *
	 * @api
	 * @return PlayerActions
	 */
	public function getPlayerActions() {
		return $this->playerActions;
	}

	/**
	 * Return the player commands
	 *
	 * @api
	 * @return PlayerCommands
	 */
	public function getPlayerCommands() {
		return $this->playerCommands;
	}

	/**
	 * Return the player detailed
	 *
	 * @api
	 * @return PlayerDetailed
	 */
	public function getPlayerDetailed() {
		return $this->playerDetailed;
	}

	/**
	 * Return the player list
	 *
	 * @api
	 * @return PlayerList
	 */
	public function getPlayerList() {
		return $this->playerList;
	}

	/**
	 * Return the admin lists
	 *
	 * @api
	 * @return AdminLists
	 */
	public function getAdminLists() {
		return $this->adminLists;
	}


	/**
	 * Handle OnInit callback
	 *
	 * @internal
	 */
	public function onInit() {
		// Add all players
		try {
			$players = $this->maniaControl->getClient()->getPlayerList(300, 0, 2);
		} catch (ParseException $e) {
			//TODO remove later, its for the wrong XML encoding of nadeo
			return;
		}

		foreach ($players as $playerItem) {
			if ($playerItem->playerId <= 0) {
				continue;
			}
			try {
				$detailedPlayerInfo = $this->maniaControl->getClient()->getDetailedPlayerInfo($playerItem->login);
			} catch (UnknownPlayerException $exception) {
				continue;
			}

			// Check if the Player is in a Team, to notify if its a TeamMode or not
			if ($playerItem->teamId >= 0) {
				$this->maniaControl->getServer()->setTeamMode(true);
			}

			$player = new Player($this->maniaControl, true);
			$player->setInfo($playerItem);
			$player->setDetailedInfo($detailedPlayerInfo);
			$player->hasJoinedGame = true;
			$this->addPlayer($player);
		}
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
	 * Save player in database and fill up properties
	 *
	 * @param Player $player
	 * @return bool
	 */
	private function savePlayer(Player &$player) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();

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
	 * Handle PlayerConnect Callback
	 *
	 * @internal
	 * @param array $callback
	 */
	public function playerConnect(array $callback) {
		$login = $callback[1][0];
		try {
			$playerInfo = $this->maniaControl->getClient()->getDetailedPlayerInfo($login);
			$player     = new Player($this->maniaControl, true);
			$player->setDetailedInfo($playerInfo);

			$this->addPlayer($player);
		} catch (UnknownPlayerException $e) {
		} catch (ParseException $e) {
			$this->maniaControl->getClient()->kick($login, "\$f00You have an unallowed character in your nickname, please remove it!");
			Logger::logError("Player With unallowed nickname joined and got kicked, login: " . $login);
			//TODO remove later
		}
	}

	/**
	 * Handle PlayerDisconnect callback
	 *
	 * @internal
	 * @param array $callback
	 */
	public function playerDisconnect(array $callback) {
		$login  = $callback[1][0];
		$player = $this->removePlayer($login);
		if (!$player) {
			return;
		}

		// Trigger own callbacks
		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_PLAYERDISCONNECT, $player);
		if ($this->getPlayerCount(false) <= 0) {
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_SERVER_EMPTY);
		}

		if ($player->isFakePlayer()) {
			return;
		}

		$playTime   = Formatter::formatTimeH(time() - $player->joinTime);
		$logMessage = "Player left: {$player->login} / {$player->nickname} Playtime: {$playTime}";
		Logger::logInfo($logMessage, true);

		if (!$player->isSpectator && $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_JOIN_LEAVE_MESSAGES)
		    || $player->isSpectator && $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_JOIN_LEAVE_MESSAGES_SPECTATOR)
		) {
			$color = '$0f0';
			if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_JOIN_LEAVE_COLORING)) {
				$color = $this->maniaControl->getColorManager()->getColorByPlayer($player);
			}

			$authName = $player->getAuthLevelName();
			$message = $this->maniaControl->getChat()->formatMessage(
				"{$color}{$authName} %s has left after %s!",
				$player,
				$playTime
			);
			$this->maniaControl->getChat()->sendChat($message);
		}

		//Destroys stored PlayerData, after all Disconnect Callbacks got Handled
		$this->getPlayerDataManager()->destroyPlayerData($player);
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

		return $this->maniaControl->getStatisticManager()->insertStat(self::STAT_SERVERTIME, $player, $this->maniaControl->getServer()->index, $playedTime);
	}

	/**
	 * Get the count of all Players
	 *
	 * @api
	 * @param bool $withoutSpectators
	 * @param bool $withoutBots
	 * @return int
	 */
	public function getPlayerCount($withoutSpectators = true, $withoutBots = true) {
		$count = 0;
		foreach ($this->players as $player) {
			$valid = true;
			if ($withoutSpectators) {
				if ($player->isSpectator) {
					$valid = false;
				}
			}
			if ($withoutBots) {
				if ($player->isFakePlayer()) {
					$valid = false;
				}
			}
			if ($valid) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Return the player data manager
	 *
	 * @api
	 * @return PlayerDataManager
	 */
	public function getPlayerDataManager() {
		return $this->playerDataManager;
	}

	/**
	 * Update PlayerInfo
	 *
	 * @internal
	 * @param array $callback
	 */
	public function playerInfoChanged(array $callback) {
		$player = $this->getPlayer($callback[1][0]['Login']);
		if (!$player) {
			return;
		}

		$player->ladderRank = $callback[1][0]["LadderRanking"];
		$player->teamId     = $callback[1][0]["TeamId"];

		//Check if the Player is in a Team, to notify if its a TeamMode or not
		if ($player->teamId >= 0) {
			$this->maniaControl->getServer()->setTeamMode(true);
		}

		$prevJoinState = $player->hasJoinedGame;

		$player->updatePlayerFlags($callback[1][0]["Flags"]);
		$player->updateSpectatorStatus($callback[1][0]["SpectatorStatus"]);

		//Check if Player finished joining the game
		if ($player->hasJoinedGame && !$prevJoinState) {
			$color = '$0f0';
			if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_JOIN_LEAVE_COLORING)) {
				$color = $this->maniaControl->getColorManager()->getColorByPlayer($player);
			}

			$authName = $player->getAuthLevelName();
			$nation = $player->getCountry();

			if (!$player->isSpectator && $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_JOIN_LEAVE_MESSAGES) && !$player->isFakePlayer()) {
				$message = $this->maniaControl->getChat()->formatMessage(
					"{$color}{$authName} %s Nation: %s joined!",
					$player,
					$nation
				);
				$this->maniaControl->getChat()->sendChat($message);
			} else if ($player->isSpectator && $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_JOIN_LEAVE_MESSAGES_SPECTATOR)) {
				$message = $this->maniaControl->getChat()->formatMessage(
					"{$color}{$authName} %s Nation: %s joined as Spectator!",
					$player,
					$nation
				);
				$this->maniaControl->getChat()->sendChat($message);
			}

			$this->maniaControl->getChat()->sendInformation('This server uses ManiaControl v' . ManiaControl::VERSION . '!', $player);

			$logMessage = "Player joined: {$player->login} / {$player->nickname} Nation: {$nation} IP: {$player->ipAddress}";
			Logger::logInfo($logMessage, true);

			// Increment the Player Join Count
			$this->maniaControl->getStatisticManager()->incrementStat(self::STAT_JOIN_COUNT, $player, $this->maniaControl->getServer()->index);

			// Trigger own PlayerJoined callback
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_PLAYERCONNECT, $player);
		}

		// Trigger own callback
		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_PLAYERINFOCHANGED, $player);

		//Avoid Multiple Triggers
		if ((microtime(true) - 0.5) > $this->playerInfosChangedTime) {
			//Delay Callback by a short Time (200ms) to be sure that different changes get submitted the same time
			$this->maniaControl->getTimerManager()->registerOneTimeListening($this, function () {
				$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_PLAYERINFOSCHANGED);
			}, 200);
		}

		$this->playerInfosChangedTime = microtime(true);
	}

	/**
	 * Get a Player by login
	 *
	 * @api
	 * @param mixed $login
	 * @param bool  $connectedPlayersOnly
	 * @return Player
	 */
	public function getPlayer($login, $connectedPlayersOnly = false) {
		if ($login instanceof Player) {
			return $login;
		}
		if (!isset($this->players[$login])) {
			if ($connectedPlayersOnly) {
				return null;
			}
			return $this->getPlayerFromDatabaseByLogin($login);
		}
		return $this->players[$login];
	}

	/**
	 * Get a Player from the database
	 *
	 * @param string $playerLogin
	 * @return Player
	 */
	private function getPlayerFromDatabaseByLogin($playerLogin) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();

		$query  = "SELECT * FROM `" . self::TABLE_PLAYERS . "`
				WHERE `login` LIKE '" . $mysqli->escape_string($playerLogin) . "';";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return null;
		}

		$row = $result->fetch_object();
		$result->free();

		if (!isset($row)) {
			return null;
		}

		$player              = new Player($this->maniaControl, false);
		$player->index       = $row->index;
		$player->login       = $row->login;
		$player->rawNickname = $row->nickname;
		$player->nickname    = Formatter::stripDirtyCodes($player->rawNickname);
		$player->path        = $row->path;
		$player->authLevel   = $row->authLevel;

		return $player;
	}

	/**
	 * Get all Players with or without spectators
	 *
	 * @api
	 * @param bool $withoutSpectators
	 * @return \ManiaControl\Players\Player[]
	 */
	public function getPlayers($withoutSpectators = false) {
		if ($withoutSpectators) {
			$players = array();
			foreach ($this->players as $player) {
				if (!$player->isSpectator) {
					$players[] = $player;
				}
			}

			return $players;
		}
		return $this->players;
	}

	/**
	 * Get a List of Spectators
	 *
	 * @api
	 * @return Player[]
	 */
	public function getSpectators() {
		$spectators = array();
		foreach ($this->players as $player) {
			if ($player->isSpectator) {
				$spectators[] = $player;
			}
		}

		return $spectators;
	}

	/**
	 * Get the count of all spectators
	 *
	 * @api
	 * @return int
	 */
	public function getSpectatorCount() {
		$count = 0;
		foreach ($this->players as $player) {
			if ($player->isSpectator) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Get a Player by index
	 *
	 * @api
	 * @param int  $index
	 * @param bool $connectedPlayersOnly
	 * @return Player
	 */
	public function getPlayerByIndex($index, $connectedPlayersOnly = false) {
		foreach ($this->players as $player) {
			if ($player->index === $index) {
				return $player;
			}
		}

		// Player is not online - Get Player from Database
		if (!$connectedPlayersOnly) {
			return $this->getPlayerFromDatabaseByIndex($index);
		}

		return null;
	}

	/**
	 * Get a Player out of the database
	 *
	 * @param int $playerIndex
	 * @return Player
	 */
	private function getPlayerFromDatabaseByIndex($playerIndex) {
		if (!is_numeric($playerIndex)) {
			return null;
		}

		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "SELECT * FROM `" . self::TABLE_PLAYERS . "`
				WHERE `index` = {$playerIndex};";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return null;
		}

		$row = $result->fetch_object();
		$result->free();

		if (!$row) {
			return null;
		}

		$player              = new Player($this->maniaControl, false);
		$player->index       = $playerIndex;
		$player->login       = $row->login;
		$player->rawNickname = $row->nickname;
		$player->nickname    = Formatter::stripDirtyCodes($player->rawNickname);
		$player->path        = $row->path;
		$player->authLevel   = $row->authLevel;

		return $player;
	}
}

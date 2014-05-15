<?php

namespace ManiaControl\Players;

use ManiaControl\ManiaControl;
use ManiaControl\Utils\ClassUtil;
use ManiaControl\Utils\Formatter;

/**
 * Player Model Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Player {
	/*
	 * Public Properties
	 */
	public $index = -1;
	public $pid = -1;
	public $login = null;
	public $nickname = null;
	public $rawNickname = null;
	public $path = null;
	public $authLevel = 0;
	public $language = null;
	public $avatar = null;
	public $allies = array();
	public $clubLink = null;
	public $teamId = -1;
	public $isOfficial = null;
	public $ladderScore = -1.;
	public $ladderRank = -1;
	public $ladderStats = null;
	public $joinTime = -1;
	public $ipAddress = null;
	public $isConnected = true;
	public $clientVersion = null;
	public $downloadRate = -1;
	public $uploadRate = -1;
	public $skins = null;
	public $daysSinceZoneInscription = -1;

	//Flags details
	public $forcedSpectatorState = 0;
	public $isReferee = false;
	public $isPodiumReady = false;
	public $isUsingStereoscopy = false;
	public $isManagedByAnOtherServer = false;
	public $isServer = false;
	public $hasPlayerSlot = false;
	public $isBroadcasting = false;
	public $hasJoinedGame = false;

	//SpectatorStatus details
	public $isSpectator = false;
	public $isTemporarySpectator = false;
	public $isPureSpectator = false;
	public $autoTarget = false;
	public $currentTargetId = 0;

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $cache = array();

	/**
	 * Construct a new Player
	 *
	 * @param ManiaControl $maniaControl
	 * @param bool         $connected
	 */
	public function __construct(ManiaControl $maniaControl, $connected) {
		$this->maniaControl = $maniaControl;
		$this->isConnected  = (bool)$connected;
		if ($connected) {
			$this->joinTime = time();
		}
	}

	/**
	 * Get the Login of the Player
	 *
	 * @param mixed $player
	 * @return string
	 */
	public static function parseLogin($player) {
		if (is_object($player) && property_exists($player, 'login')) {
			return (string)$player->login;
		}
		return (string)$player;
	}

	/**
	 * Get the Escaped Nickname
	 *
	 * @return string
	 */
	public function getEscapedNickname() {
		$nickname = $this->nickname;
		if (!$nickname) {
			$nickname = $this->login;
		}
		return Formatter::escapeText($nickname);
	}

	/**
	 * Update from ManiaPlanet PlayerInfo structure
	 *
	 * @param \Maniaplanet\DedicatedServer\Structures\PlayerInfo $mpPlayer
	 */
	public function setInfo($mpPlayer) {
		$this->pid         = $mpPlayer->playerId;
		$this->login       = $mpPlayer->login;
		$this->nickname    = Formatter::stripDirtyCodes($mpPlayer->nickName);
		$this->rawNickname = $mpPlayer->nickName;
		$this->teamId      = $mpPlayer->teamId;
		$this->isOfficial  = $mpPlayer->isInOfficialMode;

		//Flag Details
		$this->forcedSpectatorState     = $mpPlayer->forceSpectator;
		$this->isReferee                = $mpPlayer->isReferee;
		$this->isPodiumReady            = $mpPlayer->isPodiumReady;
		$this->isUsingStereoscopy       = $mpPlayer->isUsingStereoscopy;
		$this->isServer                 = $mpPlayer->isServer;
		$this->isManagedByAnOtherServer = $mpPlayer->isManagedByAnOtherServer;
		$this->hasPlayerSlot            = $mpPlayer->hasPlayerSlot;
		$this->hasJoinedGame            = $mpPlayer->hasJoinedGame;
		$this->isBroadcasting           = $mpPlayer->isBroadcasting;

		//Spectator Status
		$this->isSpectator          = $mpPlayer->spectator;
		$this->isTemporarySpectator = $mpPlayer->temporarySpectator;
		$this->isPureSpectator      = $mpPlayer->pureSpectator;
		$this->autoTarget           = $mpPlayer->autoTarget;
		$this->currentTargetId      = $mpPlayer->currentTargetId;

		if (!$this->nickname) {
			$this->nickname = $this->login;
		}
	}

	/**
	 * Update from ManiaPlanet PlayerDetailedInfo structure
	 *
	 * @param \Maniaplanet\DedicatedServer\Structures\PlayerDetailedInfo $mpPlayer
	 */
	public function setDetailedInfo($mpPlayer) {
		$this->pid                      = $mpPlayer->playerId;
		$this->login                    = $mpPlayer->login;
		$this->nickname                 = Formatter::stripDirtyCodes($mpPlayer->nickName);
		$this->rawNickname              = $mpPlayer->nickName;
		$this->path                     = $mpPlayer->path;
		$this->language                 = $mpPlayer->language;
		$this->avatar                   = $mpPlayer->avatar->fileName;
		$this->allies                   = $mpPlayer->allies;
		$this->clubLink                 = $mpPlayer->clubLink;
		$this->teamId                   = $mpPlayer->teamId;
		$this->isOfficial               = $mpPlayer->isInOfficialMode;
		$this->ladderScore              = $mpPlayer->ladderStats->playerRankings[0]->score;
		$this->ladderRank               = $mpPlayer->ladderStats->playerRankings[0]->ranking;
		$this->ladderStats              = $mpPlayer->ladderStats;
		$this->daysSinceZoneInscription = $mpPlayer->hoursSinceZoneInscription / 24;
		$this->ipAddress                = $mpPlayer->iPAddress;
		$this->clientVersion            = $mpPlayer->clientVersion;
		$this->downloadRate             = $mpPlayer->downloadRate;
		$this->uploadRate               = $mpPlayer->uploadRate;
		$this->skins                    = $mpPlayer->skins;

		if (!$this->nickname) {
			$this->nickname = $this->login;
		}
	}

	/**
	 * Check if player is not a real player
	 *
	 * @return bool
	 */
	public function isFakePlayer() {
		return ($this->pid <= 0 || $this->path == "");
	}

	/**
	 * Get province
	 *
	 * @return string
	 */
	public function getProvince() {
		return $this->getPathPart(3);
	}

	/**
	 * Get the specified Part of the Path
	 *
	 * @param int $partNumber
	 * @return string
	 */
	public function getPathPart($partNumber) {
		$pathParts = explode('|', $this->path);
		for ($partIndex = $partNumber; $partIndex >= 0; $partIndex--) {
			if (isset($pathParts[$partIndex])) {
				return $pathParts[$partIndex];
			}
		}
		return $this->path;
	}

	/**
	 * Get Country
	 *
	 * @return string
	 */
	public function getCountry() {
		return $this->getPathPart(2);
	}

	/**
	 * Get Continent
	 *
	 * @return string
	 */
	public function getContinent() {
		return $this->getPathPart(1);
	}

	/**
	 * Update the Flags of the Player
	 *
	 * @param $flags
	 */
	public function updatePlayerFlags($flags) {
		//Detail flags
		$this->forcedSpectatorState     = $flags % 10; // 0, 1 or 2
		$this->isReferee                = (bool)(intval($flags / 10) % 10);
		$this->isPodiumReady            = (bool)(intval($flags / 100) % 10);
		$this->isUsingStereoscopy       = (bool)(intval($flags / 1000) % 10);
		$this->isManagedByAnOtherServer = (bool)(intval($flags / 10000) % 10);
		$this->isServer                 = (bool)(intval($flags / 100000) % 10);
		$this->hasPlayerSlot            = (bool)(intval($flags / 1000000) % 10);
		$this->isBroadcasting           = (bool)(intval($flags / 10000000) % 10);
		$this->hasJoinedGame            = (bool)(intval($flags / 100000000) % 10);
	}

	/**
	 * Update the Spectator Status of the player
	 *
	 * @param $spectatorStatus
	 */
	public function updateSpectatorStatus($spectatorStatus) {
		//Details spectatorStatus
		$this->isSpectator          = (bool)($spectatorStatus % 10);
		$this->isTemporarySpectator = (bool)(intval($spectatorStatus / 10) % 10);
		$this->isPureSpectator      = (bool)(intval($spectatorStatus / 100) % 10);
		$this->autoTarget           = (bool)(intval($spectatorStatus / 1000) % 10);
		$this->currentTargetId      = intval($spectatorStatus / 10000);
	}

	/**
	 * Get the Cache with the given Name
	 *
	 * @param        $object
	 * @param string $cacheName
	 * @return mixed
	 */
	public function getCache($object, $cacheName) {
		$className = ClassUtil::getClass($object);
		if (isset($this->cache[$className . $cacheName])) {
			return $this->cache[$className . $cacheName];
		}
		return null;
	}

	/**
	 * Set the Cache Data for the given Name
	 *
	 * @param mixed  $object
	 * @param string $cacheName
	 * @param mixed  $data
	 */
	public function setCache($object, $cacheName, $data) {
		$className                            = ClassUtil::getClass($object);
		$this->cache[$className . $cacheName] = $data;
	}

	/**
	 * Destroy a Cache
	 *
	 * @param mixed  $object
	 * @param string $cacheName
	 */
	public function destroyCache($object, $cacheName) {
		$className = ClassUtil::getClass($object);
		unset($this->cache[$className . $cacheName]);
	}

	/**
	 * Clear the Player's Temporary Data
	 */
	public function clearCache() {
		$this->cache = array();
	}

	/**
	 * Gets the Player Data
	 *
	 * @param mixed  $object
	 * @param string $dataName
	 * @param int    $serverIndex
	 * @return mixed
	 */
	public function getPlayerData($object, $dataName, $serverIndex = -1) {
		return $this->maniaControl->playerManager->playerDataManager->getPlayerData($object, $dataName, $this, $serverIndex);
	}

	/**
	 * Sets the Player Data
	 *
	 * @param mixed  $object
	 * @param string $dataName
	 * @param mixed  $value
	 * @param int    $serverIndex
	 * @return bool
	 */
	public function setPlayerData($object, $dataName, $value, $serverIndex = -1) {
		return $this->maniaControl->playerManager->playerDataManager->setPlayerData($object, $dataName, $this, $value, $serverIndex);
	}

	/**
	 * Var_Dump the Player
	 */
	public function dump() {
		var_dump(json_decode(json_encode($this)));
	}

	/**
	 * Var_Dump the Players Cache
	 */
	public function dumpCache() {
		var_dump($this->cache);
	}
}

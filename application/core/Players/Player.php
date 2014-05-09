<?php

namespace ManiaControl\Players;

use ManiaControl\Formatter;
use ManiaControl\ManiaControl;

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
	public $login = '';
	public $nickname = '';
	public $rawNickname = '';
	public $path = '';
	public $authLevel = 0;
	public $language = '';
	public $avatar = '';
	public $allies = array();
	public $clubLink = '';
	public $teamId = -1;
	public $isOfficial = false;
	public $ladderScore = -1.;
	public $ladderRank = -1;
	public $ladderStats = null;
	public $joinTime = -1;
	public $ipAddress = '';
	public $isConnected = true;
	public $clientVersion = '';
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

	/**
	 * Construct a new Player
	 *
	 * @param ManiaControl $maniaControl
	 * @param bool $connected
	 */
	public function __construct(ManiaControl $maniaControl, $connected) {
		$this->maniaControl = $maniaControl;
		$this->isConnected  = (bool)$connected;
		if ($connected) {
			$this->joinTime = time();
		}
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
}

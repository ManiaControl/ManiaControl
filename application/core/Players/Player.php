<?php

namespace ManiaControl\Players;

use ManiaControl\Formatter;

/**
 * Player Model Class
 *
 * @author kremsy & steeffeen
 */
class Player {
	/**
	 * Public properties
	 */
	public $index = -1;
	public $pid = -1;
	public $login = '';
	public $nickname = '';
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
	public $stateUpdateLatency; //TODO format?
	public $stateUpdatePeriod; //TODO format?
	public $latestNetworkActivity; //TODO format?
	public $packetLossRate; //TODO format?
	public $maniaPlanetPlayDays = -1;

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


	/**
	 * Construct a player from XmlRpc data
	 *
	 * @param \Maniaplanet\DedicatedServer\Structures\Player $mpPlayer
	 */
	public function __construct($mpPlayer) {
		if(!$mpPlayer) {
			$this->isConnected = false;
			return;
		}

		$this->pid                   = $mpPlayer->playerId;
		$this->login                 = $mpPlayer->login;
		$this->nickname              = Formatter::stripDirtyCodes($mpPlayer->nickName);
		$this->path                  = $mpPlayer->path;
		$this->language              = $mpPlayer->language;
		$this->avatar                = $mpPlayer->avatar['FileName'];
		$this->allies                = $mpPlayer->allies;
		$this->clubLink              = $mpPlayer->clubLink;
		$this->teamId                = $mpPlayer->teamId;
		$this->isOfficial            = $mpPlayer->isInOfficialMode;
		$this->ladderScore           = $mpPlayer->ladderStats['PlayerRankings'][0]['Score'];
		$this->ladderRank            = $mpPlayer->ladderStats['PlayerRankings'][0]['Ranking'];
		$this->ladderStats           = $mpPlayer->ladderStats;
		$this->maniaPlanetPlayDays   = $mpPlayer->hoursSinceZoneInscription / 24; //TODO change
		$this->ipAddress             = $mpPlayer->iPAddress;
		$this->clientVersion         = $mpPlayer->clientVersion;
		$this->downloadRate          = $mpPlayer->downloadRate;
		$this->uploadRate            = $mpPlayer->uploadRate;
		$this->skins                 = $mpPlayer->skins;
		$this->stateUpdateLatency    = $mpPlayer->stateUpdateLatency;
		$this->stateUpdatePeriod     = $mpPlayer->stateUpdatePeriod;
		$this->latestNetworkActivity = $mpPlayer->latestNetworkActivity;
		$this->packetLossRate        = $mpPlayer->packetLossRate;


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


		$this->joinTime = time();

		if($this->nickname == '') {
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
		$pathParts = explode('|', $this->path);
		if(isset($pathParts[3])) {
			return $pathParts[3];
		}
		return $this->getCountry();
	}

	/**
	 * Get country
	 *
	 * @return string
	 */
	public function getCountry() {
		$pathParts = explode('|', $this->path);
		if(isset($pathParts[2])) {
			return $pathParts[2];
		}
		if(isset($pathParts[1])) {
			return $pathParts[1];
		}
		if(isset($pathParts[0])) {
			return $pathParts[0];
		}
		return $this->path;
	}

	/**
	 * Get continent
	 *
	 * @return string
	 */
	public function getContinent() {
		$pathParts = explode('|', $this->path);
		if(isset($pathParts[1])) {
			return $pathParts[1];
		}
		if(isset($pathParts[0])) {
			return $pathParts[0];
		}
		return $this->path;
	}

	/**
	 * Updates the Flags of the Player
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
	 * Updates the Spectator Status of the player
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

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
	public $isSpectator = false;
	public $isOfficial = false;
	public $isReferee = false;
	public $ladderScore = -1.;
	public $ladderRank = -1;
	public $joinTime = -1;
	public $ipAddress = '';
	public $maniaPlanetPlayDays = 0;

	/**
	 * Construct a player from XmlRpc data
	 *
	 * @param \Maniaplanet\DedicatedServer\Structures\Player $mpPlayer
	 */
	public function __construct($mpPlayer) {
		if(!$mpPlayer) {
			return;
		}

		$rpcInfos = (array)$mpPlayer; //Temporary

		$this->pid                 = $mpPlayer->playerId;
		$this->login               = $mpPlayer->login;
		$this->nickname            = Formatter::stripDirtyCodes($mpPlayer->nickName);
		$this->path                = $mpPlayer->path;
		$this->language            = $mpPlayer->language;
		$this->avatar              = $mpPlayer->avatar['FileName'];
		$this->allies              = $mpPlayer->allies;
		$this->clubLink            = $mpPlayer->clubLink;
		$this->teamId              = $mpPlayer->teamId;
		$this->isSpectator         = $mpPlayer->isSpectator;
		$this->isOfficial          = $mpPlayer->isInOfficialMode;
		$this->isReferee           = $mpPlayer->isReferee;
		$this->ladderScore         = $mpPlayer->ladderStats['PlayerRankings'][0]['Score'];
		$this->ladderRank          = $mpPlayer->ladderStats['PlayerRankings'][0]['Ranking'];
		$this->maniaPlanetPlayDays = $mpPlayer->hoursSinceZoneInscription / 24;

		$this->ipAddress = $mpPlayer->iPAddress;

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
}
